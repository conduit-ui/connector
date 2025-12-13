<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

/**
 * Exception thrown when GitHub API returns a server error.
 */
class GitHubServerException extends GitHubException
{
    public function __construct(
        string $message = 'GitHub API server error',
        $response = null,
        int $code = 500,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $response, $code, $previous);

        $this->setRecoverySuggestion(
            'This is a GitHub server issue. Try again later or check GitHub status page.'
        );
    }
}
