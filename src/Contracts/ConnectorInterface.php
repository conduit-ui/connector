<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Contracts;

use ConduitUi\GitHubConnector\Exceptions\NoRepoContextException;
use ConduitUi\GitHubConnector\ValueObjects\Repository;
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

    /**
     * Set the current repository context.
     *
     * @param  string|Repository  $repository  Repository in owner/repo format or Repository instance
     */
    public static function forRepo(string|Repository $repository): void;

    /**
     * Get the current repository context, or null if not set.
     *
     * @return Repository|null The repository, or null if not set
     */
    public static function repo(): ?Repository;

    /**
     * Get the current repository context, or throw if not set.
     *
     * @return Repository The repository
     *
     * @throws NoRepoContextException If no repository context is set
     */
    public static function requireRepo(): Repository;

    /**
     * Get the owner from the current repository context.
     *
     * @return string The repository owner
     *
     * @throws NoRepoContextException If no repository context is set
     */
    public static function owner(): string;

    /**
     * Get the repository name from the current repository context.
     *
     * @return string The repository name
     *
     * @throws NoRepoContextException If no repository context is set
     */
    public static function repoName(): string;

    /**
     * Clear the current repository context.
     */
    public static function clearRepo(): void;
}
