<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Auth;

use ConduitUi\GitHubConnector\Exceptions\DeviceFlowException;

/**
 * Default HTTP client implementation for device flow.
 *
 * Uses PHP's built-in file_get_contents with stream context.
 */
final class DefaultDeviceFlowHttpClient implements DeviceFlowHttpClient
{
    /**
     * Make a POST request and return the JSON response.
     *
     * @param  string  $url  The URL to post to
     * @param  array<string, string>  $params  Form parameters
     * @return array<string, mixed> Decoded JSON response
     *
     * @throws DeviceFlowException If the request fails
     */
    public function post(string $url, array $params): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($params),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new DeviceFlowException('network_error', 'Failed to connect to GitHub');
        }

        /** @var array<string, mixed> */
        return json_decode($response, true) ?? [];
    }

    /**
     * Sleep for the specified number of seconds.
     */
    public function sleep(int $seconds): void
    {
        sleep($seconds);
    }
}
