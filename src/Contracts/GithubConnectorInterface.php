<?php

namespace ConduitUi\GitHubConnector\Contracts;

use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * Contract for GitHub API connector implementations.
 */
interface ConnectorInterface
{
    /**
     * Send a Saloon request to the GitHub API.
     *
     * @param  Request  $request  The Saloon request to send
     * @return Response The response from the API
     */
    public function send(Request $request): Response;
}
