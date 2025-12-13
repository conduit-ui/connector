<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use Exception;
use Saloon\Http\Response;

/**
 * Base exception for all GitHub API errors.
 */
class GitHubException extends Exception
{
    protected ?Response $response = null;

    /** @var array<string, mixed>|null */
    protected ?array $githubError = null;

    protected ?string $recoverySuggestion = null;

    /**
     * Create a new GitHub exception.
     *
     * @param  string  $message  Exception message
     * @param  Response|null  $response  The HTTP response that caused the exception
     * @param  int  $code  Exception code
     * @param  Exception|null  $previous  Previous exception
     */
    public function __construct(
        string $message = '',
        ?Response $response = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->response = $response;

        if ($response !== null) {
            $this->parseGitHubError($response);
        }
    }

    /**
     * Get the HTTP response that caused this exception.
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Get the GitHub error details from the response.
     *
     * @return array<string, mixed>|null
     */
    public function getGitHubError(): ?array
    {
        return $this->githubError;
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
     * Parse GitHub error details from the response.
     */
    protected function parseGitHubError(Response $response): void
    {
        /** @var mixed $body */
        $body = $response->json();

        if (is_array($body) && isset($body['message'])) {
            /** @var array<string, mixed> $body */
            $this->githubError = $body;
        }
    }

    /**
     * Get a detailed error message including GitHub error details.
     */
    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();

        if ($this->githubError !== null && isset($this->githubError['message'])) {
            $message .= ' GitHub says: '.$this->githubError['message'];
        }

        if ($this->recoverySuggestion !== null) {
            $message .= ' Suggestion: '.$this->recoverySuggestion;
        }

        return $message;
    }
}
