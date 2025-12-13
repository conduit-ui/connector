<?php

use ConduitUi\GitHubConnector\Connector;
use ConduitUi\GitHubConnector\Exceptions\GithubAuthException;
use ConduitUi\GitHubConnector\Exceptions\GitHubException;
use ConduitUi\GitHubConnector\Exceptions\GitHubForbiddenException;
use ConduitUi\GitHubConnector\Exceptions\GitHubRateLimitException;
use ConduitUi\GitHubConnector\Exceptions\GitHubResourceNotFoundException;
use ConduitUi\GitHubConnector\Exceptions\GitHubServerException;
use ConduitUi\GitHubConnector\Exceptions\GitHubValidationException;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Http\Response;

function createMockResponse(array $body, int $status, array $headers = []): Response
{
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
        $request::class => MockResponse::make($body, $status, $headers),
    ]);

    $connector->withMockClient($mockClient);

    return $connector->send($request);
}

describe('GitHubException', function () {
    describe('constructor', function () {
        it('can create a basic exception', function () {
            $exception = new GitHubException('Test error');

            expect($exception->getMessage())->toBe('Test error')
                ->and($exception->getResponse())->toBeNull()
                ->and($exception->getGitHubError())->toBeNull()
                ->and($exception->getRecoverySuggestion())->toBeNull();
        });

        it('can create exception with code', function () {
            $exception = new GitHubException('Error', null, 500);

            expect($exception->getCode())->toBe(500);
        });

        it('can create exception with previous exception', function () {
            $previous = new Exception('Previous');
            $exception = new GitHubException('Error', null, 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });

        it('parses GitHub error from response with message', function () {
            $response = createMockResponse(['message' => 'Not Found', 'documentation_url' => 'https://docs.github.com'], 404);

            $exception = new GitHubException('Error', $response);

            expect($exception->getGitHubError())->toBe([
                'message' => 'Not Found',
                'documentation_url' => 'https://docs.github.com',
            ]);
        });

        it('does not parse GitHub error from response without message', function () {
            $response = createMockResponse(['error' => 'something'], 500);

            $exception = new GitHubException('Error', $response);

            expect($exception->getGitHubError())->toBeNull();
        });
    });

    describe('getDetailedMessage', function () {
        it('returns base message when no extras', function () {
            $exception = new GitHubException('Base error');

            expect($exception->getDetailedMessage())->toBe('Base error');
        });

        it('includes GitHub message when available', function () {
            $response = createMockResponse(['message' => 'Bad credentials'], 401);

            $exception = new GitHubException('Auth error', $response);

            expect($exception->getDetailedMessage())->toContain('GitHub says: Bad credentials');
        });

        it('includes recovery suggestion when set', function () {
            $exception = new GithubAuthException('Auth failed');

            expect($exception->getDetailedMessage())->toContain('Suggestion: Check your GitHub token');
        });

        it('includes both GitHub message and suggestion', function () {
            $response = createMockResponse(['message' => 'Bad credentials'], 401);

            $exception = new GithubAuthException('Auth failed', $response);

            $detailed = $exception->getDetailedMessage();
            expect($detailed)->toContain('GitHub says: Bad credentials')
                ->and($detailed)->toContain('Suggestion: Check your GitHub token');
        });
    });

    describe('getResponse', function () {
        it('returns the response when set', function () {
            $response = createMockResponse(['message' => 'Error'], 500);

            $exception = new GitHubException('Error', $response);

            expect($exception->getResponse())->toBe($response);
        });
    });
});

describe('GithubAuthException', function () {
    it('has default code 401', function () {
        $exception = new GithubAuthException;

        expect($exception->getCode())->toBe(401);
    });

    it('has recovery suggestion', function () {
        $exception = new GithubAuthException;

        expect($exception->getRecoverySuggestion())
            ->toBe('Check your GitHub token is valid and has the required permissions.');
    });
});

describe('GitHubForbiddenException', function () {
    it('has default code 403', function () {
        $exception = new GitHubForbiddenException;

        expect($exception->getCode())->toBe(403);
    });

    it('has recovery suggestion', function () {
        $exception = new GitHubForbiddenException;

        expect($exception->getRecoverySuggestion())
            ->toBe('Check that your token has the required permissions or that the resource is publicly accessible.');
    });
});

describe('GitHubResourceNotFoundException', function () {
    it('has default code 404', function () {
        $exception = new GitHubResourceNotFoundException;

        expect($exception->getCode())->toBe(404);
    });

    it('has recovery suggestion', function () {
        $exception = new GitHubResourceNotFoundException;

        expect($exception->getRecoverySuggestion())
            ->toBe('Check that the repository, user, or resource exists and you have access to it.');
    });
});

describe('GitHubServerException', function () {
    it('has default code 500', function () {
        $exception = new GitHubServerException;

        expect($exception->getCode())->toBe(500);
    });

    it('has recovery suggestion', function () {
        $exception = new GitHubServerException;

        expect($exception->getRecoverySuggestion())
            ->toBe('This is a GitHub server issue. Try again later or check GitHub status page.');
    });

    it('accepts custom code for different 5xx errors', function () {
        $exception = new GitHubServerException('Gateway error', null, 502);

        expect($exception->getCode())->toBe(502);
    });
});

describe('GitHubRateLimitException', function () {
    it('has default code 403', function () {
        $exception = new GitHubRateLimitException;

        expect($exception->getCode())->toBe(403);
    });

    it('has default null values without response', function () {
        $exception = new GitHubRateLimitException;

        expect($exception->getLimit())->toBeNull()
            ->and($exception->getRemaining())->toBeNull()
            ->and($exception->getResetTime())->toBeNull();
    });

    it('parses rate limit headers from response', function () {
        $response = createMockResponse(['message' => 'Rate limited'], 403, [
            'X-RateLimit-Limit' => '5000',
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => '1700000000',
        ]);

        $exception = new GitHubRateLimitException('Rate limited', $response);

        expect($exception->getLimit())->toBe(5000)
            ->and($exception->getRemaining())->toBe(0)
            ->and($exception->getResetTime())->toBe(1700000000);
    });

    it('handles missing rate limit headers', function () {
        $response = createMockResponse(['message' => 'Rate limited'], 403);

        $exception = new GitHubRateLimitException('Rate limited', $response);

        expect($exception->getLimit())->toBe(0)
            ->and($exception->getRemaining())->toBe(0)
            ->and($exception->getResetTime())->toBe(0);
    });

    it('calculates seconds until reset', function () {
        $futureTime = time() + 3600;
        $response = createMockResponse(['message' => 'Rate limited'], 403, [
            'X-RateLimit-Reset' => (string) $futureTime,
        ]);

        $exception = new GitHubRateLimitException('Rate limited', $response);
        $seconds = $exception->getSecondsUntilReset();

        expect($seconds)->toBeGreaterThan(3500)
            ->and($seconds)->toBeLessThanOrEqual(3600);
    });

    it('returns null for seconds until reset when no reset time', function () {
        $exception = new GitHubRateLimitException;

        expect($exception->getSecondsUntilReset())->toBeNull();
    });

    it('returns null for seconds until reset when reset time is zero', function () {
        $response = createMockResponse(['message' => 'Rate limited'], 403, [
            'X-RateLimit-Reset' => '0',
        ]);

        $exception = new GitHubRateLimitException('Rate limited', $response);

        expect($exception->getSecondsUntilReset())->toBeNull();
    });

    it('builds recovery suggestion with reset time', function () {
        $futureTime = time() + 3600;
        $response = createMockResponse(['message' => 'Rate limited'], 403, [
            'X-RateLimit-Reset' => (string) $futureTime,
        ]);

        $exception = new GitHubRateLimitException('Rate limited', $response);

        expect($exception->getRecoverySuggestion())
            ->toContain('Wait for rate limit to reset at')
            ->toContain('minutes')
            ->toContain('exponential backoff');
    });

    it('builds simple recovery suggestion without reset time', function () {
        $exception = new GitHubRateLimitException;

        expect($exception->getRecoverySuggestion())
            ->toBe('Wait for rate limit to reset or implement exponential backoff.');
    });
});

describe('GitHubValidationException', function () {
    it('has default code 422', function () {
        $exception = new GitHubValidationException;

        expect($exception->getCode())->toBe(422);
    });

    it('has empty validation errors by default', function () {
        $exception = new GitHubValidationException;

        expect($exception->getValidationErrors())->toBe([]);
    });

    it('parses validation errors from response', function () {
        $response = createMockResponse([
            'message' => 'Validation Failed',
            'errors' => [
                ['resource' => 'Issue', 'field' => 'title', 'code' => 'missing_field'],
                ['resource' => 'Issue', 'field' => 'body', 'code' => 'invalid'],
            ],
        ], 422);

        $exception = new GitHubValidationException('Validation failed', $response);

        expect($exception->getValidationErrors())->toHaveCount(2)
            ->and($exception->getValidationErrors()[0]['field'])->toBe('title');
    });

    it('handles response without errors array', function () {
        $response = createMockResponse([
            'message' => 'Validation Failed',
        ], 422);

        $exception = new GitHubValidationException('Validation failed', $response);

        expect($exception->getValidationErrors())->toBe([]);
    });

    it('includes validation errors in detailed message', function () {
        $response = createMockResponse([
            'message' => 'Validation Failed',
            'errors' => [
                ['field' => 'title', 'code' => 'missing'],
            ],
        ], 422);

        $exception = new GitHubValidationException('Validation failed', $response);

        expect($exception->getDetailedMessage())
            ->toContain('Validation errors:')
            ->toContain('title')
            ->toContain('missing');
    });

    it('has recovery suggestion', function () {
        $exception = new GitHubValidationException;

        expect($exception->getRecoverySuggestion())
            ->toBe('Check the request data format and ensure all required fields are provided correctly.');
    });
});
