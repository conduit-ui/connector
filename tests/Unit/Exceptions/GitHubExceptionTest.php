<?php

use ConduitUi\GitHubConnector\Connector;
use ConduitUi\GitHubConnector\Exceptions\GithubAuthException;
use ConduitUi\GitHubConnector\Exceptions\GitHubException;
use ConduitUi\GitHubConnector\Exceptions\GitHubRateLimitException;
use ConduitUi\GitHubConnector\Exceptions\GitHubResourceNotFoundException;
use ConduitUi\GitHubConnector\Exceptions\GitHubValidationException;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('can create a basic GitHub exception', function () {
    $exception = new GitHubException('Test error');

    expect($exception->getMessage())->toBe('Test error')
        ->and($exception->getResponse())->toBeNull()
        ->and($exception->getGitHubError())->toBeNull()
        ->and($exception->getRecoverySuggestion())->toBeNull();
});

it('creates auth exception with recovery suggestion', function () {
    $exception = new GithubAuthException('Auth failed');

    expect($exception->getCode())->toBe(401)
        ->and($exception->getRecoverySuggestion())
        ->toBe('Check your GitHub token is valid and has the required permissions.');
});

it('creates rate limit exception with default values', function () {
    $exception = new GitHubRateLimitException('Rate limited');

    expect($exception->getCode())->toBe(403)
        ->and($exception->getLimit())->toBeNull()
        ->and($exception->getRemaining())->toBeNull()
        ->and($exception->getResetTime())->toBeNull()
        ->and($exception->getRecoverySuggestion())->toContain('Wait for rate limit');
});

describe('GitHubRateLimitException', function () {
    it('parses rate limit headers from response', function () {
        $connector = new Connector('test-token');
        $resetTime = time() + 3600; // 1 hour from now

        $request = new class extends Request
        {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make(['message' => 'Rate limit exceeded'], 403, [
                'X-RateLimit-Limit' => '5000',
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) $resetTime,
            ]),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GitHubRateLimitException('Rate limited', $response);

        expect($exception->getLimit())->toBe(5000)
            ->and($exception->getRemaining())->toBe(0)
            ->and($exception->getResetTime())->toBe($resetTime);
    });

    it('calculates seconds until reset correctly', function () {
        $connector = new Connector('test-token');
        $resetTime = time() + 300; // 5 minutes from now

        $request = new class extends Request
        {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make(['message' => 'Rate limit exceeded'], 403, [
                'X-RateLimit-Reset' => (string) $resetTime,
            ]),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GitHubRateLimitException('Rate limited', $response);

        $seconds = $exception->getSecondsUntilReset();
        expect($seconds)->toBeGreaterThanOrEqual(295) // Allow for slight timing differences
            ->and($seconds)->toBeLessThanOrEqual(300);
    });

    it('returns null for seconds until reset when no reset time', function () {
        $exception = new GitHubRateLimitException('Rate limited');

        expect($exception->getSecondsUntilReset())->toBeNull();
    });

    it('includes reset time in recovery suggestion when available', function () {
        $connector = new Connector('test-token');
        $resetTime = time() + 3600; // 1 hour from now

        $request = new class extends Request
        {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make(['message' => 'Rate limit exceeded'], 403, [
                'X-RateLimit-Reset' => (string) $resetTime,
            ]),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GitHubRateLimitException('Rate limited', $response);

        $suggestion = $exception->getRecoverySuggestion();
        expect($suggestion)->toContain('Wait for rate limit to reset at')
            ->and($suggestion)->toContain('minutes')
            ->and($suggestion)->toContain('or implement exponential backoff.');
    });
});

it('creates resource not found exception', function () {
    $exception = new GitHubResourceNotFoundException('Resource missing');

    expect($exception->getCode())->toBe(404)
        ->and($exception->getRecoverySuggestion())
        ->toBe('Check that the repository, user, or resource exists and you have access to it.');
});

it('creates validation exception with default values', function () {
    $exception = new GitHubValidationException('Validation failed');

    expect($exception->getCode())->toBe(422)
        ->and($exception->getValidationErrors())->toBe([])
        ->and($exception->getRecoverySuggestion())
        ->toBe('Check the request data format and ensure all required fields are provided correctly.');
});

describe('GitHubValidationException', function () {
    it('parses validation errors from response', function () {
        $connector = new Connector('test-token');

        $request = new class extends Request
        {
            protected Method $method = Method::POST;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make([
                'message' => 'Validation Failed',
                'errors' => [
                    ['field' => 'title', 'code' => 'missing_field'],
                    ['field' => 'body', 'code' => 'invalid'],
                ],
            ], 422),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GitHubValidationException('Validation failed', $response);

        expect($exception->getValidationErrors())->toBe([
            ['field' => 'title', 'code' => 'missing_field'],
            ['field' => 'body', 'code' => 'invalid'],
        ]);
    });

    it('handles response without errors array', function () {
        $connector = new Connector('test-token');

        $request = new class extends Request
        {
            protected Method $method = Method::POST;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make(['message' => 'Validation Failed'], 422),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GitHubValidationException('Validation failed', $response);

        expect($exception->getValidationErrors())->toBe([]);
    });

    it('includes validation errors in detailed message', function () {
        $connector = new Connector('test-token');

        $request = new class extends Request
        {
            protected Method $method = Method::POST;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make([
                'message' => 'Validation Failed',
                'errors' => [
                    ['field' => 'title', 'code' => 'missing_field'],
                ],
            ], 422),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GitHubValidationException('Validation failed', $response);

        $detailed = $exception->getDetailedMessage();
        expect($detailed)->toContain('Validation failed')
            ->and($detailed)->toContain('GitHub says: Validation Failed')
            ->and($detailed)->toContain('Validation errors:')
            ->and($detailed)->toContain('missing_field');
    });

    it('does not include validation errors in detailed message when empty', function () {
        $connector = new Connector('test-token');

        $request = new class extends Request
        {
            protected Method $method = Method::POST;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make(['message' => 'Validation Failed'], 422),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GitHubValidationException('Validation failed', $response);

        $detailed = $exception->getDetailedMessage();
        expect($detailed)->toContain('Validation failed')
            ->and($detailed)->toContain('GitHub says: Validation Failed')
            ->and($detailed)->not->toContain('Validation errors:');
    });
});

describe('getDetailedMessage', function () {
    it('returns basic message when no github error or recovery suggestion', function () {
        $exception = new GitHubException('Test error');

        expect($exception->getDetailedMessage())->toBe('Test error');
    });

    it('includes github error message when present', function () {
        $connector = new Connector('test-token');

        $request = new class extends Request
        {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make(['message' => 'GitHub error details'], 500),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GitHubException('Test error', $response);

        expect($exception->getDetailedMessage())
            ->toBe('Test error GitHub says: GitHub error details');
    });

    it('includes recovery suggestion when present', function () {
        $exception = new GithubAuthException('Auth failed');

        expect($exception->getDetailedMessage())
            ->toContain('Suggestion: Check your GitHub token');
    });

    it('includes both github error and recovery suggestion', function () {
        $connector = new Connector('test-token');

        $request = new class extends Request
        {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        };

        $mockClient = new MockClient([
            $request::class => MockResponse::make(['message' => 'Bad credentials'], 401),
        ]);

        $connector->withMockClient($mockClient);
        $response = $connector->send($request);
        $exception = new GithubAuthException('Auth failed', $response);

        $detailed = $exception->getDetailedMessage();
        expect($detailed)->toContain('GitHub says: Bad credentials')
            ->and($detailed)->toContain('Suggestion: Check your GitHub token');
    });
});
