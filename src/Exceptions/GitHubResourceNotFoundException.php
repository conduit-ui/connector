<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use Saloon\Http\Response;

/**
 * Exception thrown when a GitHub resource is not found.
 */
class GitHubResourceNotFoundException extends GitHubException
{
    public function __construct(
        string $message = 'GitHub resource not found',
        ?Response $response = null,
        int $code = 404,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $response, $code, $previous);

        $this->setRecoverySuggestion(
            'Check that the repository, user, or resource exists and you have access to it.'
        );
    }
}
