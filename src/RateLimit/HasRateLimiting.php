<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\RateLimit;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
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
     * Only retries rate limit errors and server errors (5xx).
     */
    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        if ($this->rateLimitConfig === null) {
            return false;
        }

        if ($exception instanceof FatalRequestException) {
            return true;
        }

        $response = $exception->getResponse();
        $this->rateLimitState()->updateFromResponse($response);

        return $this->isRetryableStatus($response);
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
     * Named to avoid collision with Saloon's auto-boot mechanism
     * which calls boot{TraitName}(PendingRequest).
     */
    protected function initializeRateLimiting(): void
    {
        $this->rateLimitState = new RateLimitState;
    }
}
