<?php

use ConduitUi\GitHubConnector\Exceptions\DeviceFlowException;

it('can be instantiated with error and description', function () {
    $exception = new DeviceFlowException('test_error', 'Test description');

    expect($exception)->toBeInstanceOf(DeviceFlowException::class)
        ->and($exception->getMessage())->toBe('Test description');
});

it('returns the error code', function () {
    $exception = new DeviceFlowException('test_error', 'Test description');

    expect($exception->getError())->toBe('test_error');
});

it('returns the error description', function () {
    $exception = new DeviceFlowException('test_error', 'Test description');

    expect($exception->getErrorDescription())->toBe('Test description');
});

it('creates an expired exception', function () {
    $exception = DeviceFlowException::expired();

    expect($exception->getError())->toBe('expired_token')
        ->and($exception->getErrorDescription())->toBe('The device code has expired. Please restart the authentication flow.');
});

it('creates an access denied exception', function () {
    $exception = DeviceFlowException::accessDenied();

    expect($exception->getError())->toBe('access_denied')
        ->and($exception->getErrorDescription())->toBe('The user denied the authorization request.');
});
