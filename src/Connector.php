<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector;

use ConduitUi\GitHubConnector\Contracts\ConnectorInterface;
use ConduitUi\GitHubConnector\Exceptions\GithubAuthException;
use ConduitUi\GitHubConnector\Exceptions\GitHubForbiddenException;
use ConduitUi\GitHubConnector\Exceptions\GitHubRateLimitException;
use ConduitUi\GitHubConnector\Exceptions\GitHubResourceNotFoundException;
use ConduitUi\GitHubConnector\Exceptions\GitHubServerException;
use ConduitUi\GitHubConnector\Exceptions\GitHubValidationException;
use ConduitUi\GitHubConnector\Exceptions\NoRepoContextException;
use ConduitUi\GitHubConnector\ValueObjects\Repository;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector as SaloonConnector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

/**
 * GitHub API connector for Saloon HTTP client.
 */
class Connector extends SaloonConnector implements ConnectorInterface
{
    use AcceptsJson;

    protected ?string $token;

    /**
     * Static repository context.
     */
    protected static ?Repository $currentRepo = null;

    /**
     * Create a new GitHub connector instance.
     *
     * @param  string|null  $token  GitHub personal access token
     */
    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    /**
     * Get the base URL for the GitHub API.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.github.com';
    }

    /**
     * Configure default authentication for requests.
     */
    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->token);
    }

    /**
     * Configure default headers for all requests.
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    /**
     * Handle GitHub-specific exceptions based on response status.
     */
    public function getRequestException(Response $response, ?\Throwable $senderException = null): ?\Throwable
    {
        return match ($response->status()) {
            401 => new GithubAuthException('GitHub authentication failed', $response),
            403 => $this->handleForbiddenResponse($response),
            404 => new GitHubResourceNotFoundException('GitHub resource not found', $response),
            422 => new GitHubValidationException('GitHub API validation failed', $response),
            500, 502, 503, 504 => new GitHubServerException('GitHub API server error', $response, $response->status()),
            default => parent::getRequestException($response, $senderException),
        };
    }

    /**
     * Determine if the request should be considered failed.
     */
    public function hasRequestFailed(Response $response): bool
    {
        return $response->failed();
    }

    /**
     * Handle 403 responses which could be rate limiting or permissions.
     */
    protected function handleForbiddenResponse(Response $response): ?\Throwable
    {
        $headers = $response->headers();

        // Check if this is a rate limit issue
        $rateLimitRemaining = $headers->get('X-RateLimit-Remaining');
        if ($rateLimitRemaining !== null && (int) $rateLimitRemaining === 0) {
            return new GitHubRateLimitException('GitHub API rate limit exceeded', $response);
        }

        return new GitHubForbiddenException('Access to GitHub resource is forbidden', $response);
    }

    /**
     * Set the current repository context.
     *
     * Once set, all ecosystem packages (issue, pr, commit, etc.) can access
     * this context without requiring the repo to be passed explicitly.
     *
     * @param  string|Repository  $repository  Repository in owner/repo format or Repository instance
     *
     * @throws \ConduitUi\GitHubConnector\Exceptions\InvalidRepositoryException If repository format is invalid
     *
     * @example
     * ```php
     * Connector::forRepo('conduit-ui/connector');
     *
     * // Now all packages inherit this context
     * Issue::all();           // Issues from conduit-ui/connector
     * PullRequests::open();   // PRs from conduit-ui/connector
     * ```
     */
    public static function forRepo(string|Repository $repository): void
    {
        static::$currentRepo = $repository instanceof Repository
            ? $repository
            : Repository::fromString($repository);
    }

    /**
     * Get the current repository context, or null if not set.
     *
     * Use this when you want to check if a context exists without throwing.
     *
     * @return Repository|null The repository, or null if not set
     *
     * @example
     * ```php
     * if (Connector::repo() !== null) {
     *     $owner = Connector::owner();
     * }
     * ```
     */
    public static function repo(): ?Repository
    {
        return static::$currentRepo;
    }

    /**
     * Get the current repository context, or throw if not set.
     *
     * Use this when the repo context is required for the operation to proceed.
     *
     * @return Repository The repository
     *
     * @throws NoRepoContextException If no repository context is set
     *
     * @example
     * ```php
     * try {
     *     $repo = Connector::requireRepo();
     * } catch (NoRepoContextException $e) {
     *     // Handle missing context
     * }
     * ```
     */
    public static function requireRepo(): Repository
    {
        return static::$currentRepo
            ?? throw new NoRepoContextException;
    }

    /**
     * Get the owner from the current repository context.
     *
     * @return string The repository owner (e.g., 'conduit-ui' from 'conduit-ui/connector')
     *
     * @throws NoRepoContextException If no repository context is set
     *
     * @example
     * ```php
     * Connector::forRepo('conduit-ui/connector');
     * echo Connector::owner(); // 'conduit-ui'
     * ```
     */
    public static function owner(): string
    {
        return static::requireRepo()->owner;
    }

    /**
     * Get the repository name from the current repository context.
     *
     * @return string The repository name (e.g., 'connector' from 'conduit-ui/connector')
     *
     * @throws NoRepoContextException If no repository context is set
     *
     * @example
     * ```php
     * Connector::forRepo('conduit-ui/connector');
     * echo Connector::repoName(); // 'connector'
     * ```
     */
    public static function repoName(): string
    {
        return static::requireRepo()->name;
    }

    /**
     * Clear the current repository context.
     *
     * After calling this, repo() returns null and requireRepo() throws.
     *
     * @example
     * ```php
     * Connector::forRepo('owner/repo');
     * Connector::clearRepo();
     * Connector::repo(); // null
     * ```
     */
    public static function clearRepo(): void
    {
        static::$currentRepo = null;
    }
}
