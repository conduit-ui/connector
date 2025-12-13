<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a repository identifier is invalid.
 */
class InvalidRepositoryException extends InvalidArgumentException
{
    protected ?string $recoverySuggestion = null;

    public function __construct(
        string $message = 'Invalid repository identifier',
        protected readonly ?string $repository = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->setRecoverySuggestion(
            "Repository must be in 'owner/repo' format (e.g., 'laravel/framework'). "
            .'Owner and repo names may only contain alphanumeric characters, hyphens, underscores, and periods.'
        );
    }

    /**
     * Get the invalid repository string that caused this exception.
     */
    public function getRepository(): ?string
    {
        return $this->repository;
    }

    /**
     * Get a recovery suggestion for this error.
     */
    public function getRecoverySuggestion(): ?string
    {
        return $this->recoverySuggestion;
    }

    /**
     * Set a recovery suggestion for this error.
     */
    protected function setRecoverySuggestion(string $suggestion): void
    {
        $this->recoverySuggestion = $suggestion;
    }

    /**
     * Create exception for missing slash separator.
     */
    public static function missingSlash(string $repository): self
    {
        return new self(
            "Repository '{$repository}' must contain a '/' separator between owner and repo name",
            $repository
        );
    }

    /**
     * Create exception for empty owner.
     */
    public static function emptyOwner(string $repository): self
    {
        return new self(
            'Repository owner cannot be empty',
            $repository
        );
    }

    /**
     * Create exception for empty repo name.
     */
    public static function emptyName(string $repository): self
    {
        return new self(
            'Repository name cannot be empty',
            $repository
        );
    }

    /**
     * Create exception for invalid characters.
     */
    public static function invalidCharacters(string $segment, string $label): self
    {
        return new self(
            "{$label} '{$segment}' contains invalid characters. Only alphanumeric characters, hyphens, underscores, and periods are allowed."
        );
    }

    /**
     * Create exception for invalid hyphen placement.
     */
    public static function invalidHyphenPlacement(string $segment, string $label): self
    {
        return new self(
            "{$label} '{$segment}' cannot start or end with a hyphen"
        );
    }
}
