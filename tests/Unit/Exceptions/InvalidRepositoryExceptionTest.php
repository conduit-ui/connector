<?php

use ConduitUi\GitHubConnector\Exceptions\InvalidRepositoryException;

describe('InvalidRepositoryException', function () {
    describe('constructor', function () {
        it('creates exception with default message', function () {
            $exception = new InvalidRepositoryException;

            expect($exception->getMessage())->toBe('Invalid repository identifier')
                ->and($exception->getRepository())->toBeNull()
                ->and($exception->getCode())->toBe(0);
        });

        it('creates exception with custom message', function () {
            $exception = new InvalidRepositoryException('Custom error');

            expect($exception->getMessage())->toBe('Custom error');
        });

        it('creates exception with repository', function () {
            $exception = new InvalidRepositoryException('Error', 'owner/repo');

            expect($exception->getRepository())->toBe('owner/repo');
        });

        it('creates exception with custom code', function () {
            $exception = new InvalidRepositoryException('Error', null, 42);

            expect($exception->getCode())->toBe(42);
        });

        it('creates exception with previous exception', function () {
            $previous = new Exception('Previous error');
            $exception = new InvalidRepositoryException('Error', null, 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });

        it('always sets recovery suggestion', function () {
            $exception = new InvalidRepositoryException;

            expect($exception->getRecoverySuggestion())
                ->toContain("'owner/repo' format")
                ->toContain('alphanumeric characters');
        });
    });

    describe('static factory methods', function () {
        it('creates missingSlash exception', function () {
            $exception = InvalidRepositoryException::missingSlash('invalid');

            expect($exception->getMessage())->toContain("'/' separator")
                ->and($exception->getRepository())->toBe('invalid');
        });

        it('creates emptyOwner exception', function () {
            $exception = InvalidRepositoryException::emptyOwner('/repo');

            expect($exception->getMessage())->toContain('owner cannot be empty')
                ->and($exception->getRepository())->toBe('/repo');
        });

        it('creates emptyName exception', function () {
            $exception = InvalidRepositoryException::emptyName('owner/');

            expect($exception->getMessage())->toContain('name cannot be empty')
                ->and($exception->getRepository())->toBe('owner/');
        });

        it('creates invalidCharacters exception', function () {
            $exception = InvalidRepositoryException::invalidCharacters('bad@name', 'Owner');

            expect($exception->getMessage())
                ->toContain("Owner 'bad@name' contains invalid characters")
                ->toContain('alphanumeric characters, hyphens, underscores, and periods');
        });

        it('creates invalidHyphenPlacement exception', function () {
            $exception = InvalidRepositoryException::invalidHyphenPlacement('-owner', 'Owner');

            expect($exception->getMessage())->toContain("Owner '-owner' cannot start or end with a hyphen");
        });
    });

    describe('inheritance', function () {
        it('extends InvalidArgumentException', function () {
            $exception = new InvalidRepositoryException;

            expect($exception)->toBeInstanceOf(InvalidArgumentException::class);
        });
    });
});
