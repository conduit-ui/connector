<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Auth;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;

/**
 * Token-based authentication strategy for GitHub API.
 *
 * This is the most common authentication method, using a GitHub
 * Personal Access Token (PAT) or fine-grained token.
 *
 * @example
 * ```php
 * $auth = new TokenAuthentication('ghp_xxxxxxxxxxxx');
 * $connector = new Connector($auth);
 * ```
 */
final class TokenAuthentication implements AuthenticationStrategy
{
    /**
     * Create a new token authentication strategy.
     *
     * @param  string  $token  GitHub personal access token or fine-grained token
     */
    public function __construct(
        private readonly string $token
    ) {}

    /**
     * Get the Saloon token authenticator.
     */
    public function getAuthenticator(): Authenticator
    {
        return new TokenAuthenticator($this->token);
    }
}
