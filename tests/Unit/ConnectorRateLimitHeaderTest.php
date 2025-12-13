<?php

use ConduitUi\GitHubConnector\Connector;
use ConduitUi\GitHubConnector\Exceptions\GitHubForbiddenException;
use ConduitUi\GitHubConnector\Exceptions\GitHubRateLimitException;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

beforeEach(function () {
    $this->connector = new Connector('test-token');
});

it('treats string "0" as rate limit exceeded', function () {
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
            'X-RateLimit-Remaining' => '0', // String zero
        ]),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(403);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubRateLimitException::class);
});

it('treats integer 0 as rate limit exceeded', function () {
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
            'X-RateLimit-Remaining' => 0, // Integer zero
        ]),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(403);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubRateLimitException::class);
});

it('treats string "5" as not rate limited', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user/repos';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'Forbidden'], 403, [
            'X-RateLimit-Remaining' => '5', // String non-zero
        ]),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(403);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubForbiddenException::class);
});

it('treats integer 5 as not rate limited', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user/repos';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'Forbidden'], 403, [
            'X-RateLimit-Remaining' => 5, // Integer non-zero
        ]),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(403);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubForbiddenException::class);
});

it('handles missing X-RateLimit-Remaining header', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user/repos';
        }
    };

    $mockClient = new MockClient([
        $request::class => MockResponse::make(['message' => 'Forbidden'], 403), // No rate limit headers
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($request);

    expect($response->status())->toBe(403);

    $exception = $this->connector->getRequestException($response);
    expect($exception)->toBeInstanceOf(GitHubForbiddenException::class);
});
