<?php

namespace ConduitUi\GitHubConnector;

use ConduitUi\GitHubConnector\Contracts\ConnectorInterface;
use ConduitUi\GitHubConnector\Exceptions\GithubAuthException;
use ConduitUi\GitHubConnector\Exceptions\GitHubForbiddenException;
use ConduitUi\GitHubConnector\Exceptions\GitHubRateLimitException;
use ConduitUi\GitHubConnector\Exceptions\GitHubResourceNotFoundException;
use ConduitUi\GitHubConnector\Exceptions\GitHubServerException;
use ConduitUi\GitHubConnector\Exceptions\GitHubValidationException;
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
}
