<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Auth;

/**
 * Callback interface for device flow authentication events.
 *
 * Implement this interface to handle the various stages of the
 * OAuth 2.0 Device Authorization Grant flow.
 *
 * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps#device-flow
 */
interface DeviceFlowCallback
{
    /**
     * Called when the device code is ready for the user.
     *
     * Display the verification URI and user code to the user.
     *
     * @param  string  $verificationUri  The URL where the user should enter the code (e.g., https://github.com/login/device)
     * @param  string  $userCode  The code the user should enter (e.g., ABCD-1234)
     * @param  int  $expiresIn  Seconds until the code expires
     */
    public function onCodeReady(string $verificationUri, string $userCode, int $expiresIn): void;

    /**
     * Called during polling while waiting for user authorization.
     *
     * Use this to display a waiting indicator or update the user.
     */
    public function onPolling(): void;

    /**
     * Called when authorization is successful.
     *
     * @param  string  $accessToken  The OAuth access token
     * @param  string  $tokenType  The token type (usually "bearer")
     * @param  string|null  $scope  The granted scopes, if any
     */
    public function onSuccess(string $accessToken, string $tokenType, ?string $scope): void;

    /**
     * Called when an error occurs during the flow.
     *
     * @param  string  $error  The error code (e.g., "access_denied", "expired_token")
     * @param  string  $errorDescription  Human-readable error description
     */
    public function onError(string $error, string $errorDescription): void;
}
