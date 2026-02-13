<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\RateLimit;

/**
 * Configuration for GitHub API rate limiting behavior.
 */
final readonly class RateLimitConfig
{
    public function __construct(
        public int $retryAttempts = 3,
        public int $retryDelay = 1000,
        public bool $useExponentialBackoff = true,
        public bool $throwOnMaxRetries = true,
    ) {}
}
