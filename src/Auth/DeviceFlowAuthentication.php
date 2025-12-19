<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Auth;

use ConduitUi\GitHubConnector\Exceptions\DeviceFlowException;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;

/**
 * OAuth 2.0 Device Flow authentication strategy for GitHub.
 *
 * This strategy implements RFC 8628 Device Authorization Grant,
 * allowing authentication in CLI and headless environments.
 *
 * @example
 * ```php
 * $auth = new DeviceFlowAuthentication(
 *     clientId: 'your_oauth_app_client_id',
 *     callback: new MyDeviceFlowHandler()
 * );
 *
 * // Start the flow (blocking until authorized or failed)
 * $auth->authorize();
 *
 * // Now use with connector
 * $connector = new Connector($auth);
 * ```
 *
 * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps#device-flow
 * @see https://datatracker.ietf.org/doc/html/rfc8628
 */
final class DeviceFlowAuthentication implements AuthenticationStrategy
{
    private const DEVICE_CODE_URL = 'https://github.com/login/device/code';

    private const ACCESS_TOKEN_URL = 'https://github.com/login/oauth/access_token';

    private ?string $accessToken = null;

    private readonly DeviceFlowHttpClient $httpClient;

    /**
     * Create a new device flow authentication strategy.
     *
     * @param  string  $clientId  OAuth App client ID
     * @param  DeviceFlowCallback  $callback  Callback handler for flow events
     * @param  string|null  $scope  Optional OAuth scopes (space-separated)
     * @param  DeviceFlowHttpClient|null  $httpClient  Optional HTTP client for testing
     */
    public function __construct(
        private readonly string $clientId,
        private readonly DeviceFlowCallback $callback,
        private readonly ?string $scope = null,
        ?DeviceFlowHttpClient $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? new DefaultDeviceFlowHttpClient;
    }

    /**
     * Start the device flow authorization process.
     *
     * This method blocks until the user completes authorization,
     * the code expires, or an error occurs.
     *
     * @throws DeviceFlowException If authorization fails
     */
    public function authorize(): void
    {
        $deviceCode = $this->requestDeviceCode();
        $this->accessToken = $this->pollForAccessToken($deviceCode);
    }

    /**
     * Check if the device flow has been completed.
     */
    public function isAuthorized(): bool
    {
        return $this->accessToken !== null;
    }

    /**
     * Get the Saloon authenticator.
     *
     * @throws DeviceFlowException If authorize() has not been called
     */
    public function getAuthenticator(): Authenticator
    {
        if ($this->accessToken === null) {
            throw new DeviceFlowException(
                'not_authorized',
                'Device flow not completed. Call authorize() first.'
            );
        }

        return new TokenAuthenticator($this->accessToken, 'Bearer');
    }

    /**
     * Request a device code from GitHub.
     *
     * @return array{device_code: string, user_code: string, verification_uri: string, expires_in: int, interval: int}
     *
     * @throws DeviceFlowException If the request fails
     */
    private function requestDeviceCode(): array
    {
        $params = ['client_id' => $this->clientId];

        if ($this->scope !== null) {
            $params['scope'] = $this->scope;
        }

        $response = $this->httpClient->post(self::DEVICE_CODE_URL, $params);

        if (isset($response['error'])) {
            throw new DeviceFlowException(
                $response['error'],
                $response['error_description'] ?? 'Failed to request device code'
            );
        }

        $this->callback->onCodeReady(
            $response['verification_uri'],
            $response['user_code'],
            $response['expires_in']
        );

        return $response;
    }

    /**
     * Poll GitHub for the access token.
     *
     * @param  array{device_code: string, user_code: string, verification_uri: string, expires_in: int, interval: int}  $deviceCode
     *
     * @throws DeviceFlowException If authorization fails or expires
     */
    private function pollForAccessToken(array $deviceCode): string
    {
        $interval = $deviceCode['interval'];
        $expiresAt = time() + $deviceCode['expires_in'];

        while (time() < $expiresAt) {
            $this->callback->onPolling();

            $this->httpClient->sleep($interval);

            $response = $this->httpClient->post(self::ACCESS_TOKEN_URL, [
                'client_id' => $this->clientId,
                'device_code' => $deviceCode['device_code'],
                'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            ]);

            if (isset($response['access_token'])) {
                $this->callback->onSuccess(
                    $response['access_token'],
                    $response['token_type'] ?? 'bearer',
                    $response['scope'] ?? null
                );

                return $response['access_token'];
            }

            if (isset($response['error'])) {
                $error = $response['error'];
                $description = $response['error_description'] ?? 'Unknown error';

                if ($error === 'authorization_pending') {
                    continue;
                }

                if ($error === 'slow_down') {
                    $interval += 5;

                    continue;
                }

                $this->callback->onError($error, $description);

                throw new DeviceFlowException($error, $description);
            }
        }

        $this->callback->onError('expired_token', 'The device code has expired.');

        throw DeviceFlowException::expired();
    }
}
