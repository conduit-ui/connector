<?php

use ConduitUi\GitHubConnector\Connector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

beforeEach(function () {
    $this->connector = new Connector('test-token');
});

it('can be instantiated with a token', function () {
    $connector = new Connector('test-token');

    expect($connector)->toBeInstanceOf(Connector::class);
});

it('can be instantiated without a token', function () {
    $connector = new Connector;

    expect($connector)->toBeInstanceOf(Connector::class);
});

it('resolves the correct base URL', function () {
    expect($this->connector->resolveBaseUrl())->toBe('https://api.github.com');
});

it('includes correct default headers', function () {
    $reflection = new ReflectionClass($this->connector);
    $method = $reflection->getMethod('defaultHeaders');
    $method->setAccessible(true);
    $headers = $method->invoke($this->connector);

    expect($headers)->toHaveKey('Accept', 'application/vnd.github.v3+json')
        ->and($headers)->toHaveKey('X-GitHub-Api-Version', '2022-11-28');
});

it('can send GET requests', function () {
    $mockClient = new MockClient([
        MockResponse::make(['message' => 'success'], 200),
    ]);

    $this->connector->withMockClient($mockClient);

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $this->connector->send($request);

    expect($response->json())->toBe(['message' => 'success']);
});

it('can send POST requests', function () {
    $mockClient = new MockClient([
        MockResponse::make(['created' => true], 201),
    ]);

    $this->connector->withMockClient($mockClient);

    $request = new class extends Request
    {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/user/repos';
        }

        protected function defaultBody(): array
        {
            return ['name' => 'test-repo'];
        }
    };

    $response = $this->connector->send($request);

    expect($response->json())->toBe(['created' => true]);
});

it('can send requests successfully', function () {
    $mockClient = new MockClient([
        MockResponse::make(['success' => true], 200),
    ]);

    $this->connector->withMockClient($mockClient);

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $this->connector->send($request);

    expect($response->json())->toBe(['success' => true])
        ->and($response->status())->toBe(200);
});

