<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\ValueObjects;

use ConduitUi\GitHubConnector\Exceptions\InvalidRepositoryException;
use Stringable;

/**
 * Value object representing a GitHub repository identifier.
 *
 * Validates and encapsulates the owner/repo format used throughout
 * the GitHub API. Immutable once created.
 *
 * @example
 * ```php
 * $repo = Repository::fromString('laravel/framework');
 * echo $repo->owner;  // 'laravel'
 * echo $repo->name;   // 'framework'
 * echo $repo;         // 'laravel/framework'
 * ```
 */
final readonly class Repository implements Stringable
{
    /**
     * Valid characters for owner and repo names.
     * GitHub allows: alphanumeric, hyphens, underscores, and periods.
     */
    private const VALID_SEGMENT_PATTERN = '/^[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]$|^[a-zA-Z0-9]$/';

    public function __construct(
        public string $owner,
        public string $name,
    ) {
        $this->validateSegment($this->owner, 'Owner');
        $this->validateSegment($this->name, 'Repository name');
    }

    /**
     * Create a Repository from an "owner/repo" string.
     *
     * @param  string  $repository  Repository in owner/repo format
     *
     * @throws InvalidRepositoryException If the format is invalid
     */
    public static function fromString(string $repository): self
    {
        if (! str_contains($repository, '/')) {
            throw InvalidRepositoryException::missingSlash($repository);
        }

        [$owner, $name] = explode('/', $repository, 2);

        return new self($owner, $name);
    }

    /**
     * Get the full repository identifier.
     */
    public function toString(): string
    {
        return "{$this->owner}/{$this->name}";
    }

    /**
     * String representation for casting.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Check equality with another Repository.
     */
    public function equals(self $other): bool
    {
        return $this->owner === $other->owner
            && $this->name === $other->name;
    }

    /**
     * Validate a segment (owner or repo name).
     *
     * @throws InvalidRepositoryException If validation fails
     */
    private function validateSegment(string $value, string $label): void
    {
        if ($value === '') {
            throw $label === 'Owner'
                ? InvalidRepositoryException::emptyOwner("{$this->owner}/{$this->name}")
                : InvalidRepositoryException::emptyName("{$this->owner}/{$this->name}");
        }

        if (str_starts_with($value, '-') || str_ends_with($value, '-')) {
            throw InvalidRepositoryException::invalidHyphenPlacement($value, $label);
        }

        if (str_starts_with($value, '.') || str_ends_with($value, '.')) {
            throw new InvalidRepositoryException(
                "{$label} '{$value}' cannot start or end with a period"
            );
        }

        if (! preg_match(self::VALID_SEGMENT_PATTERN, $value)) {
            throw InvalidRepositoryException::invalidCharacters($value, $label);
        }
    }
}
