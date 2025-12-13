<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a repo context is required but not set.
 */
class NoRepoContextException extends RuntimeException
{
    public function __construct(string $message = 'No repository context set. Call Connector::forRepo() first.')
    {
        parent::__construct($message);
    }
}
