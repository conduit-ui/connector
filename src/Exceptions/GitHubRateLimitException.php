<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use Saloon\Http\Response;

/**
 * Exception thrown when GitHub API rate limit is exceeded.
 */
class GitHubRateLimitException extends GitHubException
{
    protected ?int $resetTime = null;

    protected ?int $remaining = null;

    protected ?int $limit = null;

    public function __construct(
        string $message = 'GitHub API rate limit exceeded',
        ?Response $response = null,
        int $code = 403,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $response, $code, $previous);

        if ($response !== null) {
            $this->parseRateLimitHeaders($response);
        }

        $this->setRecoverySuggestion($this->buildRecoverySuggestion());
    }

    /**
     * Get the Unix timestamp when the rate limit resets.
     */
    public function getResetTime(): ?int
    {
        return $this->resetTime;
    }

    /**
     * Get the number of remaining requests.
     */
    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    /**
     * Get the rate limit (requests per hour).
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Get the time until rate limit reset in seconds.
     */
    public function getSecondsUntilReset(): ?int
    {
        if ($this->resetTime === null || $this->resetTime === 0) {
            return null;
        }

        return max(0, $this->resetTime - time());
    }

    /**
     * Parse rate limit information from response headers.
     */
    protected function parseRateLimitHeaders(Response $response): void
    {
        $headers = $response->headers();

        $limit = $headers->get('X-RateLimit-Limit');
        $remaining = $headers->get('X-RateLimit-Remaining');
        $reset = $headers->get('X-RateLimit-Reset');

        $this->limit = is_numeric($limit) ? (int) $limit : 0;
        $this->remaining = is_numeric($remaining) ? (int) $remaining : 0;
        $this->resetTime = is_numeric($reset) ? (int) $reset : 0;
    }

    /**
     * Build a recovery suggestion based on rate limit info.
     */
    protected function buildRecoverySuggestion(): string
    {
        $suggestion = 'Wait for rate limit to reset';

        if ($this->resetTime !== null && $this->resetTime > 0) {
            $seconds = $this->getSecondsUntilReset();
            $minutes = $seconds !== null ? (int) ceil($seconds / 60) : 0;
            $resetTime = date('H:i:s', $this->resetTime);

            $suggestion .= " at {$resetTime} (~{$minutes} minutes)";
        }

        $suggestion .= ' or implement exponential backoff.';

        return $suggestion;
    }
}
