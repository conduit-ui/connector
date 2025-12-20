<?php

use ConduitUi\GitHubConnector\Auth\DeviceFlowAuthentication;
use ConduitUi\GitHubConnector\Auth\DeviceFlowCallback;
use ConduitUi\GitHubConnector\Auth\DeviceFlowHttpClient;
use ConduitUi\GitHubConnector\Exceptions\DeviceFlowException;
use Saloon\Http\Auth\TokenAuthenticator;

/**
 * A simple test callback implementation.
 */
class TestDeviceFlowCallback implements DeviceFlowCallback
{
    public string $verificationUri = '';

    public string $userCode = '';

    public int $expiresIn = 0;

    public int $pollingCount = 0;

    public ?string $accessToken = null;

    public ?string $tokenType = null;

    public ?string $scope = null;

    public ?string $error = null;

    public ?string $errorDescription = null;

    public function onCodeReady(string $verificationUri, string $userCode, int $expiresIn): void
    {
        $this->verificationUri = $verificationUri;
        $this->userCode = $userCode;
        $this->expiresIn = $expiresIn;
    }

    public function onPolling(): void
    {
        $this->pollingCount++;
    }

    public function onSuccess(string $accessToken, string $tokenType, ?string $scope): void
    {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
        $this->scope = $scope;
    }

    public function onError(string $error, string $errorDescription): void
    {
        $this->error = $error;
        $this->errorDescription = $errorDescription;
    }
}

/**
 * A mock HTTP client for testing.
 */
class MockDeviceFlowHttpClient implements DeviceFlowHttpClient
{
    /** @var array<array<string, mixed>> */
    private array $responses = [];

    private int $callIndex = 0;

    public int $sleepCalls = 0;

    public int $lastSleepSeconds = 0;

    /**
     * @param  array<string, mixed>  $response
     */
    public function addResponse(array $response): self
    {
        $this->responses[] = $response;

        return $this;
    }

    public function post(string $url, array $params): array
    {
        if (! isset($this->responses[$this->callIndex])) {
            return [];
        }

        return $this->responses[$this->callIndex++];
    }

    public function sleep(int $seconds): void
    {
        $this->sleepCalls++;
        $this->lastSleepSeconds = $seconds;
    }
}

it('can be instantiated with required parameters', function () {
    $callback = new TestDeviceFlowCallback;
    $auth = new DeviceFlowAuthentication('client_id', $callback);

    expect($auth)->toBeInstanceOf(DeviceFlowAuthentication::class);
});

it('can be instantiated with optional scope', function () {
    $callback = new TestDeviceFlowCallback;
    $auth = new DeviceFlowAuthentication('client_id', $callback, 'repo user');

    expect($auth)->toBeInstanceOf(DeviceFlowAuthentication::class);
});

it('includes scope in device code request when provided', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 900,
        'interval' => 5,
    ]);

    $httpClient->addResponse([
        'access_token' => 'gho_test_token',
        'token_type' => 'bearer',
        'scope' => 'repo user',
    ]);

    // Pass scope to constructor
    $auth = new DeviceFlowAuthentication('client_id', $callback, 'repo user', $httpClient);
    $auth->authorize();

    expect($auth->isAuthorized())->toBeTrue()
        ->and($callback->scope)->toBe('repo user');
});

it('can be instantiated with custom HTTP client', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;
    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);

    expect($auth)->toBeInstanceOf(DeviceFlowAuthentication::class);
});

it('returns false for isAuthorized before authorize is called', function () {
    $callback = new TestDeviceFlowCallback;
    $auth = new DeviceFlowAuthentication('client_id', $callback);

    expect($auth->isAuthorized())->toBeFalse();
});

it('throws exception when getting authenticator before authorization', function () {
    $callback = new TestDeviceFlowCallback;
    $auth = new DeviceFlowAuthentication('client_id', $callback);

    $auth->getAuthenticator();
})->throws(DeviceFlowException::class, 'Device flow not completed. Call authorize() first.');

it('completes successful authorization flow', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    // First call: device code request
    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 900,
        'interval' => 5,
    ]);

    // Second call: access token response
    $httpClient->addResponse([
        'access_token' => 'gho_test_token',
        'token_type' => 'bearer',
        'scope' => 'repo',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);
    $auth->authorize();

    expect($auth->isAuthorized())->toBeTrue()
        ->and($callback->verificationUri)->toBe('https://github.com/login/device')
        ->and($callback->userCode)->toBe('ABCD-1234')
        ->and($callback->expiresIn)->toBe(900)
        ->and($callback->pollingCount)->toBe(1)
        ->and($callback->accessToken)->toBe('gho_test_token')
        ->and($callback->tokenType)->toBe('bearer')
        ->and($callback->scope)->toBe('repo');
});

it('returns TokenAuthenticator after successful authorization', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 900,
        'interval' => 5,
    ]);

    $httpClient->addResponse([
        'access_token' => 'gho_test_token',
        'token_type' => 'bearer',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);
    $auth->authorize();

    $authenticator = $auth->getAuthenticator();

    expect($authenticator)->toBeInstanceOf(TokenAuthenticator::class);
});

it('throws exception when device code request fails', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'error' => 'invalid_client',
        'error_description' => 'The client_id is not valid',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);
    $auth->authorize();
})->throws(DeviceFlowException::class, 'The client_id is not valid');

it('handles device code error without description', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'error' => 'invalid_client',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);
    $auth->authorize();
})->throws(DeviceFlowException::class, 'Failed to request device code');

it('continues polling when authorization is pending', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 900,
        'interval' => 5,
    ]);

    // First poll: pending
    $httpClient->addResponse([
        'error' => 'authorization_pending',
    ]);

    // Second poll: success
    $httpClient->addResponse([
        'access_token' => 'gho_test_token',
        'token_type' => 'bearer',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);
    $auth->authorize();

    expect($callback->pollingCount)->toBe(2)
        ->and($callback->accessToken)->toBe('gho_test_token');
});

it('increases interval on slow_down error', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 900,
        'interval' => 5,
    ]);

    // First poll: slow down
    $httpClient->addResponse([
        'error' => 'slow_down',
    ]);

    // Second poll: success
    $httpClient->addResponse([
        'access_token' => 'gho_test_token',
        'token_type' => 'bearer',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);
    $auth->authorize();

    // Interval should increase from 5 to 10 after slow_down
    expect($httpClient->lastSleepSeconds)->toBe(10);
});

it('throws exception on access denied', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 900,
        'interval' => 5,
    ]);

    $httpClient->addResponse([
        'error' => 'access_denied',
        'error_description' => 'The user denied the request',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);

    try {
        $auth->authorize();
    } catch (DeviceFlowException $e) {
        expect($e->getError())->toBe('access_denied')
            ->and($callback->error)->toBe('access_denied');

        throw $e;
    }
})->throws(DeviceFlowException::class, 'The user denied the request');

it('handles success response with default token type', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 900,
        'interval' => 5,
    ]);

    $httpClient->addResponse([
        'access_token' => 'gho_test_token',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);
    $auth->authorize();

    expect($callback->tokenType)->toBe('bearer')
        ->and($callback->scope)->toBeNull();
});

it('handles polling error without description', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 900,
        'interval' => 5,
    ]);

    $httpClient->addResponse([
        'error' => 'unknown_error',
    ]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);
    $auth->authorize();
})->throws(DeviceFlowException::class, 'Unknown error');

it('throws expired exception when device code expires', function () {
    $callback = new TestDeviceFlowCallback;
    $httpClient = new MockDeviceFlowHttpClient;

    // Device code with very short expiration (1 second)
    $httpClient->addResponse([
        'device_code' => 'abc123',
        'user_code' => 'ABCD-1234',
        'verification_uri' => 'https://github.com/login/device',
        'expires_in' => 1,
        'interval' => 0,
    ]);

    // Return empty responses (no access_token, no error) to keep loop running
    // until expiration
    $httpClient->addResponse([]);
    $httpClient->addResponse([]);
    $httpClient->addResponse([]);

    $auth = new DeviceFlowAuthentication('client_id', $callback, null, $httpClient);

    try {
        $auth->authorize();
    } catch (DeviceFlowException $e) {
        expect($callback->error)->toBe('expired_token')
            ->and($e->getError())->toBe('expired_token');

        throw $e;
    }
})->throws(DeviceFlowException::class, 'The device code has expired. Please restart the authentication flow.');
