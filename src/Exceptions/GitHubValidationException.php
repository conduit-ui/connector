<?php

declare(strict_types=1);

namespace ConduitUi\GitHubConnector\Exceptions;

use Saloon\Http\Response;

/**
 * Exception thrown when GitHub API validation fails.
 */
class GitHubValidationException extends GitHubException
{
    /** @var array<int, array<string, mixed>> */
    protected array $validationErrors = [];

    public function __construct(
        string $message = 'GitHub API validation failed',
        ?Response $response = null,
        int $code = 422,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $response, $code, $previous);

        if ($response !== null) {
            $this->parseValidationErrors($response);
        }

        $this->setRecoverySuggestion(
            'Check the request data format and ensure all required fields are provided correctly.'
        );
    }

    /**
     * Get the validation errors from GitHub.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Parse validation errors from the GitHub response.
     */
    protected function parseValidationErrors(Response $response): void
    {
        /** @var mixed $body */
        $body = $response->json();

        if (is_array($body) && isset($body['errors']) && is_array($body['errors'])) {
            /** @var array<int, array<string, mixed>> $errors */
            $errors = $body['errors'];
            $this->validationErrors = $errors;
        }
    }

    /**
     * Get a detailed error message including validation errors.
     */
    public function getDetailedMessage(): string
    {
        $message = parent::getDetailedMessage();

        if (count($this->validationErrors) > 0) {
            $encoded = json_encode($this->validationErrors);
            if ($encoded !== false) {
                $message .= ' Validation errors: '.$encoded;
            }
        }

        return $message;
    }
}
