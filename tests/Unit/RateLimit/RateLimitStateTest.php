<?php

use ConduitUi\GitHubConnector\RateLimit\RateLimitState;
use Saloon\Http\Faking\MockResponse;

it('starts with null values', function () {
    $state = new RateLimitState;

    expect($state->getLimit())->toBeNull()
        ->and($state->getRemaining())->toBeNull()
        ->and($state->getReset())->toBeNull()
        ->and($state->getUsed())->toBeNull()
        ->and($state->isExceeded())->toBeFalse();
});

it('updates from response headers', function () {
    $state = new RateLimitState;

    $resetTime = time() + 3600;
    $mockResponse = MockResponse::make('', 200, [
        'X-RateLimit-Limit' => '5000',
        'X-RateLimit-Remaining' => '4999',
        'X-RateLimit-Reset' => (string) $resetTime,
        'X-RateLimit-Used' => '1',
    ]);

    $connector = new \ConduitUi\GitHubConnector\Connector;
    $connector->withMockClient(new \Saloon\Http\Faking\MockClient([$mockResponse]));

    $request = new class extends \Saloon\Http\Request
    {
        protected \Saloon\Enums\Method $method = \Saloon\Enums\Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $connector->send($request);
    $state->updateFromResponse($response);

    expect($state->getLimit())->toBe(5000)
        ->and($state->getRemaining())->toBe(4999)
        ->and($state->getReset())->toBe($resetTime)
        ->and($state->getUsed())->toBe(1)
        ->and($state->isExceeded())->toBeFalse();
});

it('detects exceeded rate limit', function () {
    $state = new RateLimitState;

    $resetTime = time() + 3600;
    $mockResponse = MockResponse::make('', 200, [
        'X-RateLimit-Limit' => '5000',
        'X-RateLimit-Remaining' => '0',
        'X-RateLimit-Reset' => (string) $resetTime,
        'X-RateLimit-Used' => '5000',
    ]);

    $connector = new \ConduitUi\GitHubConnector\Connector;
    $connector->withMockClient(new \Saloon\Http\Faking\MockClient([$mockResponse]));

    $request = new class extends \Saloon\Http\Request
    {
        protected \Saloon\Enums\Method $method = \Saloon\Enums\Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $connector->send($request);
    $state->updateFromResponse($response);

    expect($state->isExceeded())->toBeTrue()
        ->and($state->getRemaining())->toBe(0);
});

it('calculates seconds until reset', function () {
    $state = new RateLimitState;

    $resetTime = time() + 120;
    $mockResponse = MockResponse::make('', 200, [
        'X-RateLimit-Reset' => (string) $resetTime,
    ]);

    $connector = new \ConduitUi\GitHubConnector\Connector;
    $connector->withMockClient(new \Saloon\Http\Faking\MockClient([$mockResponse]));

    $request = new class extends \Saloon\Http\Request
    {
        protected \Saloon\Enums\Method $method = \Saloon\Enums\Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $connector->send($request);
    $state->updateFromResponse($response);

    $seconds = $state->getSecondsUntilReset();
    expect($seconds)->toBeGreaterThanOrEqual(119)
        ->and($seconds)->toBeLessThanOrEqual(121);
});

it('returns null seconds when no reset time', function () {
    $state = new RateLimitState;

    expect($state->getSecondsUntilReset())->toBeNull();
});

it('returns zero seconds for past reset times', function () {
    $state = new RateLimitState;

    $pastTime = time() - 100;
    $mockResponse = MockResponse::make('', 200, [
        'X-RateLimit-Reset' => (string) $pastTime,
    ]);

    $connector = new \ConduitUi\GitHubConnector\Connector;
    $connector->withMockClient(new \Saloon\Http\Faking\MockClient([$mockResponse]));

    $request = new class extends \Saloon\Http\Request
    {
        protected \Saloon\Enums\Method $method = \Saloon\Enums\Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $connector->send($request);
    $state->updateFromResponse($response);

    expect($state->getSecondsUntilReset())->toBe(0);
});

it('converts to array', function () {
    $state = new RateLimitState;

    $result = $state->toArray();

    expect($result)->toHaveKeys(['limit', 'remaining', 'reset', 'used', 'exceeded', 'seconds_until_reset'])
        ->and($result['exceeded'])->toBeFalse();
});

it('only updates headers that are present', function () {
    $state = new RateLimitState;

    $mockResponse = MockResponse::make('', 200, [
        'X-RateLimit-Limit' => '5000',
    ]);

    $connector = new \ConduitUi\GitHubConnector\Connector;
    $connector->withMockClient(new \Saloon\Http\Faking\MockClient([$mockResponse]));

    $request = new class extends \Saloon\Http\Request
    {
        protected \Saloon\Enums\Method $method = \Saloon\Enums\Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $connector->send($request);
    $state->updateFromResponse($response);

    expect($state->getLimit())->toBe(5000)
        ->and($state->getRemaining())->toBeNull()
        ->and($state->getReset())->toBeNull()
        ->and($state->getUsed())->toBeNull();
});
