<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Auth;

use Saloon\Contracts\Authenticator;

/**
 * Contract for GitHub authentication strategies.
 *
 * Implement this interface to create custom authentication methods
 * for the GitHub connector. Each strategy is responsible for providing
 * a Saloon Authenticator that will be applied to requests.
 */
interface AuthenticationStrategy
{
    /**
     * Get the Saloon authenticator for this strategy.
     *
     * The returned authenticator will be applied to all requests
     * made through the connector.
     */
    public function getAuthenticator(): Authenticator;
}
