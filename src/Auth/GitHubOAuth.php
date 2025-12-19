<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Auth;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;

/**
 * OAuth access token authentication strategy for GitHub API.
 *
 * This strategy is used for authenticating with an OAuth access token
 * obtained through the OAuth web flow or device flow.
 *
 * @example
 * ```php
 * // After completing OAuth flow and obtaining access token
 * $auth = new GitHubOAuth($accessToken);
 * $connector = new Connector($auth);
 * ```
 *
 * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
 */
final class GitHubOAuth implements AuthenticationStrategy
{
    /**
     * Create a new OAuth authentication strategy.
     *
     * @param  string  $accessToken  OAuth access token from GitHub
     */
    public function __construct(
        private readonly string $accessToken
    ) {}

    /**
     * Get the Saloon authenticator with Bearer prefix for OAuth.
     */
    public function getAuthenticator(): Authenticator
    {
        return new TokenAuthenticator($this->accessToken, 'Bearer');
    }
}
