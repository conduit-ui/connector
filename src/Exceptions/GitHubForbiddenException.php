<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use Saloon\Http\Response;

/**
 * Exception thrown when access to a GitHub resource is forbidden.
 */
class GitHubForbiddenException extends GitHubException
{
    public function __construct(
        string $message = 'Access to GitHub resource is forbidden',
        ?Response $response = null,
        int $code = 403,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $response, $code, $previous);

        $this->setRecoverySuggestion(
            'Check that your token has the required permissions or that the resource is publicly accessible.'
        );
    }
}
