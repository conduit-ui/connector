<?php

use ConduitUi\GitHubConnector\Auth\GitHubAppAuthentication;
use ConduitUi\GitHubConnector\Auth\GitHubOAuth;
use ConduitUi\GitHubConnector\Auth\TokenAuthentication;
use ConduitUi\GitHubConnector\Connector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('can be instantiated with a TokenAuthentication strategy', function () {
    $auth = new TokenAuthentication('ghp_test_token');
    $connector = new Connector($auth);

    expect($connector)->toBeInstanceOf(Connector::class);
});

it('can be instantiated with a GitHubAppAuthentication strategy', function () {
    $auth = new GitHubAppAuthentication('jwt_token');
    $connector = new Connector($auth);

    expect($connector)->toBeInstanceOf(Connector::class);
});

it('can be instantiated with a GitHubOAuth strategy', function () {
    $auth = new GitHubOAuth('gho_access_token');
    $connector = new Connector($auth);

    expect($connector)->toBeInstanceOf(Connector::class);
});

it('converts string token to TokenAuthentication for backward compatibility', function () {
    $connector = new Connector('ghp_string_token');

    expect($connector)->toBeInstanceOf(Connector::class);
});

it('can send requests with TokenAuthentication strategy', function () {
    $auth = new TokenAuthentication('ghp_test_token');
    $connector = new Connector($auth);

    $mockClient = new MockClient([
        MockResponse::make(['authenticated' => true], 200),
    ]);

    $connector->withMockClient($mockClient);

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $connector->send($request);

    expect($response->json())->toBe(['authenticated' => true]);
});

it('can send requests with GitHubAppAuthentication strategy', function () {
    $auth = new GitHubAppAuthentication('jwt_token');
    $connector = new Connector($auth);

    $mockClient = new MockClient([
        MockResponse::make(['app' => 'authenticated'], 200),
    ]);

    $connector->withMockClient($mockClient);

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/app';
        }
    };

    $response = $connector->send($request);

    expect($response->json())->toBe(['app' => 'authenticated']);
});

it('can send requests with GitHubOAuth strategy', function () {
    $auth = new GitHubOAuth('gho_access_token');
    $connector = new Connector($auth);

    $mockClient = new MockClient([
        MockResponse::make(['oauth' => 'authenticated'], 200),
    ]);

    $connector->withMockClient($mockClient);

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/user';
        }
    };

    $response = $connector->send($request);

    expect($response->json())->toBe(['oauth' => 'authenticated']);
});
