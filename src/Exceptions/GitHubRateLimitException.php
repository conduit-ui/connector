<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

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
        $response = null,
        int $code = 403,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $response, $code, $previous);

        if ($response) {
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
        if (! $this->resetTime) {
            return null;
        }

        return max(0, $this->resetTime - time());
    }

    /**
     * Parse rate limit information from response headers.
     */
    protected function parseRateLimitHeaders($response): void
    {
        if (method_exists($response, 'headers')) {
            $headers = $response->headers();

            $this->limit = (int) ($headers->get('X-RateLimit-Limit') ?? 0);
            $this->remaining = (int) ($headers->get('X-RateLimit-Remaining') ?? 0);
            $this->resetTime = (int) ($headers->get('X-RateLimit-Reset') ?? 0);
        }
    }

    /**
     * Build a recovery suggestion based on rate limit info.
     */
    protected function buildRecoverySuggestion(): string
    {
        $suggestion = 'Wait for rate limit to reset';

        if ($this->resetTime) {
            $seconds = $this->getSecondsUntilReset();
            $minutes = $seconds ? ceil($seconds / 60) : 0;
            $resetTime = date('H:i:s', $this->resetTime);

            $suggestion .= " at {$resetTime} (~{$minutes} minutes)";
        }

        $suggestion .= ' or implement exponential backoff.';

        return $suggestion;
    }
}
