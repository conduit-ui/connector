<?php

use ConduitUi\GitHubConnector\RateLimit\RateLimitConfig;

it('creates config with default values', function () {
    $config = new RateLimitConfig;

    expect($config->retryAttempts)->toBe(3)
        ->and($config->retryDelay)->toBe(1000)
        ->and($config->useExponentialBackoff)->toBeTrue()
        ->and($config->throwOnMaxRetries)->toBeTrue();
});

it('creates config with custom values', function () {
    $config = new RateLimitConfig(
        retryAttempts: 5,
        retryDelay: 2000,
        useExponentialBackoff: false,
        throwOnMaxRetries: false,
    );

    expect($config->retryAttempts)->toBe(5)
        ->and($config->retryDelay)->toBe(2000)
        ->and($config->useExponentialBackoff)->toBeFalse()
        ->and($config->throwOnMaxRetries)->toBeFalse();
});

it('is readonly', function () {
    $config = new RateLimitConfig;

    $reflection = new ReflectionClass($config);
    expect($reflection->isReadOnly())->toBeTrue();
});
