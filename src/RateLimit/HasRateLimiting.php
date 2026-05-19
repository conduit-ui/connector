<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\RateLimit;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * Provides GitHub API rate limiting and intelligent retry behavior.
 *
 * Uses Saloon's built-in retry mechanism with GitHub-aware logic:
 * - Retries on rate limit (403 + rate limit headers) and server errors (5xx)
 * - Exponential backoff with configurable base delay
 * - Rate limit header tracking for status reporting
 *
 * Rate limit state tracking is always active. Automatic retry is opt-in
 * via withRateLimiting() to maintain backward compatibility.
 */
trait HasRateLimiting
{
    protected ?RateLimitConfig $rateLimitConfig = null;

    protected RateLimitState $rateLimitState;

    /**
     * Enable automatic retry with rate limiting configuration.
     */
    public function withRateLimiting(?RateLimitConfig $config = null): static
    {
        $this->rateLimitConfig = $config ?? new RateLimitConfig;
        $this->applyRetryConfig();

        return $this;
    }

    /**
     * Get the current rate limit state.
     */
    public function rateLimitState(): RateLimitState
    {
        return $this->rateLimitState ??= new RateLimitState;
    }

    /**
     * Get the rate limit configuration, or null if retry is not enabled.
     */
    public function rateLimitConfig(): ?RateLimitConfig
    {
        return $this->rateLimitConfig;
    }

    /**
     * Check if automatic retry is enabled.
     */
    public function hasRateLimitingEnabled(): bool
    {
        return $this->rateLimitConfig !== null;
    }

    /**
     * Determine if a failed request should be retried.
     *
     * Only retries rate limit errors and server errors (5xx). Rate limit state
     * is refreshed for every response (success and failure) via the response
     * middleware registered in {@see bootHasRateLimiting()}, so this method
     * does not need to update state itself.
     */
    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        if ($this->rateLimitConfig === null) {
            return false;
        }

        if ($exception instanceof FatalRequestException) {
            return true;
        }

        return $this->isRetryableStatus($exception->getResponse());
    }

    /**
     * Check if a response status is retryable (rate limit or server error).
     */
    protected function isRetryableStatus(Response $response): bool
    {
        $status = $response->status();

        if (in_array($status, [500, 502, 503, 504], true)) {
            return true;
        }

        if ($status === 403) {
            $remaining = $response->headers()->get('X-RateLimit-Remaining');

            return $remaining !== null && (int) $remaining === 0;
        }

        return false;
    }

    /**
     * Apply retry configuration from RateLimitConfig to Saloon's retry properties.
     */
    protected function applyRetryConfig(): void
    {
        if ($this->rateLimitConfig === null) {
            return;
        }

        $config = $this->rateLimitConfig;

        $this->tries = $config->retryAttempts;
        $this->retryInterval = $config->retryDelay;
        $this->useExponentialBackoff = $config->useExponentialBackoff;
        $this->throwOnMaxTries = $config->throwOnMaxRetries;
    }

    /**
     * Initialize the rate limiting trait.
     *
     * Called from the connector constructor to ensure {@see $rateLimitState}
     * is always available. The per-request lifecycle wiring is handled by
     * {@see bootHasRateLimiting()}.
     */
    protected function initializeRateLimiting(): void
    {
        $this->rateLimitState = new RateLimitState;
    }

    /**
     * Saloon lifecycle hook invoked once per outgoing request.
     *
     * Registers a response middleware so that {@see RateLimitState::updateFromResponse()}
     * runs for every response, regardless of HTTP status. Retry decisions still
     * flow through {@see handleRetry()}; this hook only keeps state in sync.
     */
    public function bootHasRateLimiting(PendingRequest $pendingRequest): void
    {
        $state = $this->rateLimitState();

        $pendingRequest->middleware()->onResponse(
            static function (Response $response) use ($state): Response {
                $state->updateFromResponse($response);

                return $response;
            },
            'updateRateLimitState',
        );
    }
}
