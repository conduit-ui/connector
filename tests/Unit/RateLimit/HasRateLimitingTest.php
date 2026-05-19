<?php

use ConduitUi\GitHubConnector\Connector;
use ConduitUi\GitHubConnector\RateLimit\RateLimitConfig;
use ConduitUi\GitHubConnector\RateLimit\RateLimitState;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('does not enable retry by default', function () {
    $connector = new Connector('test-token');

    expect($connector->hasRateLimitingEnabled())->toBeFalse()
        ->and($connector->rateLimitConfig())->toBeNull();
});

it('enables retry via constructor config', function () {
    $config = new RateLimitConfig(retryAttempts: 5, retryDelay: 2000);
    $connector = new Connector('test-token', $config);

    expect($connector->hasRateLimitingEnabled())->toBeTrue()
        ->and($connector->rateLimitConfig()->retryAttempts)->toBe(5)
        ->and($connector->rateLimitConfig()->retryDelay)->toBe(2000);
});

it('enables retry via fluent method', function () {
    $connector = new Connector('test-token');
    $config = new RateLimitConfig(retryAttempts: 7);

    $result = $connector->withRateLimiting($config);

    expect($result)->toBe($connector)
        ->and($connector->hasRateLimitingEnabled())->toBeTrue()
        ->and($connector->rateLimitConfig()->retryAttempts)->toBe(7);
});

it('uses default config when withRateLimiting called without args', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting();

    expect($connector->hasRateLimitingEnabled())->toBeTrue()
        ->and($connector->rateLimitConfig()->retryAttempts)->toBe(3)
        ->and($connector->rateLimitConfig()->retryDelay)->toBe(1000);
});

it('provides rate limit state', function () {
    $connector = new Connector('test-token');

    expect($connector->rateLimitState())->toBeInstanceOf(RateLimitState::class);
});

it('sets saloon retry properties from config', function () {
    $config = new RateLimitConfig(
        retryAttempts: 5,
        retryDelay: 2000,
        useExponentialBackoff: true,
        throwOnMaxRetries: false,
    );

    $connector = new Connector('test-token', $config);

    expect($connector->tries)->toBe(5)
        ->and($connector->retryInterval)->toBe(2000)
        ->and($connector->useExponentialBackoff)->toBeTrue()
        ->and($connector->throwOnMaxTries)->toBeFalse();
});

it('succeeds on first try without retry enabled', function () {
    $connector = new Connector('test-token');

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['login' => 'test'], 200, [
            'X-RateLimit-Remaining' => '4999',
            'X-RateLimit-Limit' => '5000',
        ]),
    ]);

    $connector->withMockClient($mockClient);
    $response = $connector->send($request);

    expect($response->status())->toBe(200)
        ->and($response->json())->toBe(['login' => 'test']);
});

it('retries on rate limit 403 and succeeds', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 3,
        retryDelay: 0,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'rate limit exceeded'], 403, [
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => (string) (time() + 3600),
        ]),
        MockResponse::make(['login' => 'test'], 200, [
            'X-RateLimit-Remaining' => '4999',
        ]),
    ]);

    $connector->withMockClient($mockClient);
    $response = $connector->send($request);

    expect($response->status())->toBe(200)
        ->and($response->json())->toBe(['login' => 'test']);
});

it('retries on server error 500 and succeeds', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 3,
        retryDelay: 0,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Internal Server Error'], 500),
        MockResponse::make(['login' => 'test'], 200),
    ]);

    $connector->withMockClient($mockClient);
    $response = $connector->send($request);

    expect($response->status())->toBe(200);
});

it('retries on 502 bad gateway and succeeds', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 2,
        retryDelay: 0,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make('Bad Gateway', 502),
        MockResponse::make(['login' => 'test'], 200),
    ]);

    $connector->withMockClient($mockClient);
    $response = $connector->send($request);

    expect($response->status())->toBe(200);
});

it('retries on 503 service unavailable and succeeds', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 2,
        retryDelay: 0,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make('Service Unavailable', 503),
        MockResponse::make(['login' => 'test'], 200),
    ]);

    $connector->withMockClient($mockClient);
    $response = $connector->send($request);

    expect($response->status())->toBe(200);
});

it('does not retry on 401 auth error', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 3,
        retryDelay: 0,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Bad credentials'], 401),
        MockResponse::make(['login' => 'test'], 200),
    ]);

    $connector->withMockClient($mockClient);

    expect(fn () => $connector->send($request))
        ->toThrow(\ConduitUi\GitHubConnector\Exceptions\GithubAuthException::class);
});

it('does not retry on 404 not found', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 3,
        retryDelay: 0,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/repos/nonexistent/repo';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Not Found'], 404),
        MockResponse::make(['login' => 'test'], 200),
    ]);

    $connector->withMockClient($mockClient);

    expect(fn () => $connector->send($request))
        ->toThrow(\ConduitUi\GitHubConnector\Exceptions\GitHubResourceNotFoundException::class);
});

it('does not retry on 422 validation error', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 3,
        retryDelay: 0,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/user/repos';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Validation Failed'], 422),
        MockResponse::make(['created' => true], 201),
    ]);

    $connector->withMockClient($mockClient);

    expect(fn () => $connector->send($request))
        ->toThrow(\ConduitUi\GitHubConnector\Exceptions\GitHubValidationException::class);
});

it('does not retry on 403 without rate limit headers', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 3,
        retryDelay: 0,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Forbidden'], 403),
        MockResponse::make(['login' => 'test'], 200),
    ]);

    $connector->withMockClient($mockClient);

    expect(fn () => $connector->send($request))
        ->toThrow(\ConduitUi\GitHubConnector\Exceptions\GitHubForbiddenException::class);
});

it('throws after max retries when throwOnMaxRetries is true', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 2,
        retryDelay: 0,
        throwOnMaxRetries: true,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'rate limit exceeded'], 403, [
            'X-RateLimit-Remaining' => '0',
        ]),
        MockResponse::make(['message' => 'rate limit exceeded'], 403, [
            'X-RateLimit-Remaining' => '0',
        ]),
    ]);

    $connector->withMockClient($mockClient);

    expect(fn () => $connector->send($request))->toThrow(Exception::class);
});

it('returns last response after max retries when throwOnMaxRetries is false', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 2,
        retryDelay: 0,
        throwOnMaxRetries: false,
    ));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'rate limit exceeded'], 403, [
            'X-RateLimit-Remaining' => '0',
        ]),
        MockResponse::make(['message' => 'rate limit exceeded'], 403, [
            'X-RateLimit-Remaining' => '0',
        ]),
    ]);

    $connector->withMockClient($mockClient);
    $response = $connector->send($request);

    expect($response->status())->toBe(403);
});

it('updates rate limit state on successful responses (no retry enabled)', function () {
    $connector = new Connector('test-token');

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['login' => 'test'], 200, [
            'X-RateLimit-Limit' => '5000',
            'X-RateLimit-Remaining' => '4999',
            'X-RateLimit-Used' => '1',
        ]),
    ]);

    $connector->withMockClient($mockClient);
    $connector->send($request);

    $state = $connector->rateLimitState();
    expect($state->getLimit())->toBe(5000)
        ->and($state->getRemaining())->toBe(4999)
        ->and($state->getUsed())->toBe(1);
});

it('updates rate limit state on successful responses when retry is enabled', function () {
    $connector = new Connector('test-token', new RateLimitConfig(retryAttempts: 3, retryDelay: 0));

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['login' => 'test'], 200, [
            'X-RateLimit-Limit' => '5000',
            'X-RateLimit-Remaining' => '4998',
        ]),
    ]);

    $connector->withMockClient($mockClient);
    $connector->send($request);

    $state = $connector->rateLimitState();
    expect($state->getLimit())->toBe(5000)
        ->and($state->getRemaining())->toBe(4998);
});

it('updates rate limit state during retry', function () {
    $connector = new Connector('test-token');
    $connector->withRateLimiting(new RateLimitConfig(
        retryAttempts: 2,
        retryDelay: 0,
    ));

    $resetTime = time() + 3600;

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'rate limit exceeded'], 403, [
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Limit' => '5000',
            'X-RateLimit-Reset' => (string) $resetTime,
            'X-RateLimit-Used' => '5000',
        ]),
        MockResponse::make(['login' => 'test'], 200, [
            'X-RateLimit-Remaining' => '4999',
        ]),
    ]);

    $connector->withMockClient($mockClient);
    $connector->send($request);

    $state = $connector->rateLimitState();
    expect($state->getLimit())->toBe(5000)
        ->and($state->getReset())->toBe($resetTime)
        ->and($state->getUsed())->toBe(5000);
});
