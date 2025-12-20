<?php

use ConduitUi\GitHubConnector\Auth\DefaultDeviceFlowHttpClient;
use ConduitUi\GitHubConnector\Auth\DeviceFlowHttpClient;
use ConduitUi\GitHubConnector\Exceptions\DeviceFlowException;

it('implements DeviceFlowHttpClient interface', function () {
    $client = new DefaultDeviceFlowHttpClient;

    expect($client)->toBeInstanceOf(DeviceFlowHttpClient::class);
});

it('throws DeviceFlowException on network error', function () {
    $client = new DefaultDeviceFlowHttpClient;

    // Use an invalid URL that will fail
    $client->post('http://invalid.localhost.test:99999/nonexistent', ['test' => 'value']);
})->throws(DeviceFlowException::class, 'Failed to connect to GitHub');

it('sleep method executes without error', function () {
    $client = new DefaultDeviceFlowHttpClient;

    // Sleep for 0 seconds just to cover the method
    $client->sleep(0);

    expect(true)->toBeTrue();
});

/**
 * Mock stream wrapper for testing HTTP calls.
 */
class MockHttpStreamWrapper
{
    public static string $mockResponse = '{}';

    private int $position = 0;

    /** @var resource|null */
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return true;
    }

    public function stream_read(int $count): string
    {
        $result = substr(self::$mockResponse, $this->position, $count);
        $this->position += strlen($result);

        return $result;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$mockResponse);
    }

    public function stream_stat(): array
    {
        return [];
    }
}

it('returns parsed JSON response on successful request', function () {
    // Register the mock stream wrapper
    stream_wrapper_unregister('http');
    stream_wrapper_register('http', MockHttpStreamWrapper::class);

    try {
        // Set the mock response
        MockHttpStreamWrapper::$mockResponse = '{"access_token": "test_token", "token_type": "bearer"}';

        $client = new DefaultDeviceFlowHttpClient;
        $result = $client->post('http://example.com/test', ['param' => 'value']);

        expect($result)->toBe(['access_token' => 'test_token', 'token_type' => 'bearer']);
    } finally {
        // Restore the original http stream wrapper
        stream_wrapper_restore('http');
    }
});

it('returns empty array when response is not valid JSON', function () {
    // Register the mock stream wrapper
    stream_wrapper_unregister('http');
    stream_wrapper_register('http', MockHttpStreamWrapper::class);

    try {
        // Set invalid JSON response
        MockHttpStreamWrapper::$mockResponse = 'not valid json';

        $client = new DefaultDeviceFlowHttpClient;
        $result = $client->post('http://example.com/test', ['param' => 'value']);

        expect($result)->toBe([]);
    } finally {
        // Restore the original http stream wrapper
        stream_wrapper_restore('http');
    }
});
