<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Auth;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;

/**
 * GitHub App authentication strategy using JWT.
 *
 * This strategy is used for authenticating as a GitHub App.
 * It accepts a pre-generated JWT token that should be created
 * using the App's private key and App ID.
 *
 * Note: JWT generation is left to the consumer as it requires
 * a JWT library. This strategy accepts the generated JWT directly.
 *
 * @example
 * ```php
 * // Generate JWT externally (using firebase/php-jwt or similar)
 * $jwt = generateAppJwt($appId, $privateKey);
 *
 * $auth = new GitHubAppAuthentication($jwt);
 * $connector = new Connector($auth);
 * ```
 *
 * @see https://docs.github.com/en/apps/creating-github-apps/authenticating-with-a-github-app/about-authentication-with-a-github-app
 */
final class GitHubAppAuthentication implements AuthenticationStrategy
{
    /**
     * Create a new GitHub App authentication strategy.
     *
     * @param  string  $jwt  Pre-generated JWT token for the GitHub App
     */
    public function __construct(
        private readonly string $jwt
    ) {}

    /**
     * Get the Saloon authenticator with Bearer prefix for JWT.
     */
    public function getAuthenticator(): Authenticator
    {
        return new TokenAuthenticator($this->jwt, 'Bearer');
    }
}
