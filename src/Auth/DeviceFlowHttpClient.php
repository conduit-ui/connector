<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Auth;

use ConduitUi\GitHubConnector\Exceptions\DeviceFlowException;

/**
 * HTTP client interface for device flow operations.
 *
 * Implement this interface to customize HTTP behavior or for testing.
 */
interface DeviceFlowHttpClient
{
    /**
     * Make a POST request and return the JSON response.
     *
     * @param  string  $url  The URL to post to
     * @param  array<string, string>  $params  Form parameters
     * @return array<string, mixed>  Decoded JSON response
     *
     * @throws DeviceFlowException If the request fails
     */
    public function post(string $url, array $params): array;

    /**
     * Sleep for the specified number of seconds.
     *
     * This is separated to allow tests to skip actual sleeping.
     */
    public function sleep(int $seconds): void;
}
