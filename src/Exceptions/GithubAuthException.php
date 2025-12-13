<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use Saloon\Http\Response;

/**
 * Exception thrown when GitHub authentication fails.
 */
class GithubAuthException extends GitHubException
{
    public function __construct(
        string $message = 'GitHub authentication failed',
        ?Response $response = null,
        int $code = 401,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $response, $code, $previous);

        $this->setRecoverySuggestion(
            'Check your GitHub token is valid and has the required permissions.'
        );
    }
}
