<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\RateLimit;

use Saloon\Http\Response;

/**
 * Tracks the current GitHub API rate limit state from response headers.
 */
final class RateLimitState
{
    private ?int $limit = null;

    private ?int $remaining = null;

    private ?int $reset = null;

    private ?int $used = null;

    /**
     * Update state from a Saloon response's rate limit headers.
     */
    public function updateFromResponse(Response $response): void
    {
        $headers = $response->headers();

        $limit = $headers->get('X-RateLimit-Limit');
        $remaining = $headers->get('X-RateLimit-Remaining');
        $reset = $headers->get('X-RateLimit-Reset');
        $used = $headers->get('X-RateLimit-Used');

        if ($limit !== null) {
            $this->limit = (int) $limit;
        }

        if ($remaining !== null) {
            $this->remaining = (int) $remaining;
        }

        if ($reset !== null) {
            $this->reset = (int) $reset;
        }

        if ($used !== null) {
            $this->used = (int) $used;
        }
    }

    /**
     * Check if the rate limit has been exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->remaining !== null && $this->remaining === 0;
    }

    /**
     * Get seconds until the rate limit resets.
     */
    public function getSecondsUntilReset(): ?int
    {
        if ($this->reset === null) {
            return null;
        }

        return max(0, $this->reset - time());
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    public function getReset(): ?int
    {
        return $this->reset;
    }

    public function getUsed(): ?int
    {
        return $this->used;
    }

    /**
     * Get rate limit status as an array for reporting.
     */
    public function toArray(): array
    {
        return [
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'reset' => $this->reset,
            'used' => $this->used,
            'exceeded' => $this->isExceeded(),
            'seconds_until_reset' => $this->getSecondsUntilReset(),
        ];
    }
}
