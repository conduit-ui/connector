<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use Exception;

/**
 * Exception thrown when device flow authentication fails.
 */
class DeviceFlowException extends Exception
{
    /**
     * Create a new device flow exception.
     *
     * @param  string  $error  The error code from GitHub
     * @param  string  $errorDescription  Human-readable description
     */
    public function __construct(
        private readonly string $error,
        private readonly string $errorDescription
    ) {
        parent::__construct($errorDescription);
    }

    /**
     * Get the error code.
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Get the error description.
     */
    public function getErrorDescription(): string
    {
        return $this->errorDescription;
    }

    /**
     * Create an exception for an expired token.
     */
    public static function expired(): self
    {
        return new self('expired_token', 'The device code has expired. Please restart the authentication flow.');
    }

    /**
     * Create an exception for access denied.
     */
    public static function accessDenied(): self
    {
        return new self('access_denied', 'The user denied the authorization request.');
    }
}
