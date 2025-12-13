<?php

use ConduitUi\GitHubConnector\Connector;
use ConduitUi\GitHubConnector\Exceptions\GithubAuthException;
use ConduitUi\GitHubConnector\Exceptions\GitHubForbiddenException;
use ConduitUi\GitHubConnector\Exceptions\GitHubRateLimitException;
use ConduitUi\GitHubConnector\Exceptions\GitHubResourceNotFoundException;
use ConduitUi\GitHubConnector\Exceptions\GitHubServerException;
use ConduitUi\GitHubConnector\Exceptions\GitHubValidationException;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

beforeEach(function () {
    $this->connector = new Connector('test-token');
});

it('maps 401 responses to auth exceptions', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'Bad credentials'], 401),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(401);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GithubAuthException::class);
    expect($exception->getMessage())->toBe('GitHub authentication failed');
});

it('maps 403 responses with rate limit headers to rate limit exceptions', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user/repos';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'API rate limit exceeded'], 403, [
            'X-RateLimit-Remaining' => '0',
        ]),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(403);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubRateLimitException::class);
});

it('maps 403 responses without rate limit headers to forbidden exceptions', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user/repos';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'Forbidden'], 403),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(403);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubForbiddenException::class);
});

it('maps 404 responses to resource not found exceptions', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/repos/nonexistent/repo';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'Not Found'], 404),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(404);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubResourceNotFoundException::class);
});

it('maps 422 responses to validation exceptions', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/user/repos';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'Validation Failed'], 422),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(422);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubValidationException::class);
});

it('maps 500 responses to server exceptions', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'Internal Server Error'], 500),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(500);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubServerException::class);
    expect($exception->getCode())->toBe(500);
});

it('returns null for successful responses', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['login' => 'testuser'], 200),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(200);
    expect($response->successful())->toBeTrue();

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeNull();
});
