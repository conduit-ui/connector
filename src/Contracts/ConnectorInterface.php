<?php

declare(strict_types=1);

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

    /**
     * Set the current repository context.
     *
     * @param  string  $repository  Repository in owner/repo format
     */
    public static function forRepo(string $repository): void;

    /**
     * Get the current repository context, or null if not set.
     */
    public static function repo(): ?string;

    /**
     * Get the current repository context, or throw if not set.
     */
    public static function requireRepo(): string;

    /**
     * Get the owner from the current repository context.
     */
    public static function owner(): string;

    /**
     * Get the repository name from the current repository context.
     */
    public static function repoName(): string;

    /**
     * Clear the current repository context.
     */
    public static function clearRepo(): void;
}
