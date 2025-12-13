<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

/**
 * Exception thrown when GitHub API validation fails.
 */
class GitHubValidationException extends GitHubException
{
    protected array $validationErrors = [];

    public function __construct(
        string $message = 'GitHub API validation failed',
        $response = null,
        int $code = 422,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $response, $code, $previous);

        if ($response) {
            $this->parseValidationErrors($response);
        }

        $this->setRecoverySuggestion(
            'Check the request data format and ensure all required fields are provided correctly.'
        );
    }

    /**
     * Get the validation errors from GitHub.
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Parse validation errors from the GitHub response.
     */
    protected function parseValidationErrors($response): void
    {
        if (method_exists($response, 'json')) {
            $body = $response->json();

            if (is_array($body) && isset($body['errors']) && is_array($body['errors'])) {
                $this->validationErrors = $body['errors'];
            }
        }
    }

    /**
     * Get a detailed error message including validation errors.
     */
    public function getDetailedMessage(): string
    {
        $message = parent::getDetailedMessage();

        if (! empty($this->validationErrors)) {
            $message .= ' Validation errors: '.json_encode($this->validationErrors);
        }

        return $message;
    }
}
