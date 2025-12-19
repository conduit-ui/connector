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
