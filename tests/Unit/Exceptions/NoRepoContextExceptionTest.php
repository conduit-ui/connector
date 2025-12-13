<?php

use ConduitUi\GitHubConnector\Exceptions\NoRepoContextException;

describe('NoRepoContextException', function () {
    describe('constructor', function () {
        it('creates exception with default message', function () {
            $exception = new NoRepoContextException;

            expect($exception->getMessage())->toBe('No repository context set. Call Connector::forRepo() first.')
                ->and($exception->getCode())->toBe(0);
        });

        it('creates exception with custom message', function () {
            $exception = new NoRepoContextException('Custom message');

            expect($exception->getMessage())->toBe('Custom message');
        });

        it('creates exception with custom code', function () {
            $exception = new NoRepoContextException('Error', 42);

            expect($exception->getCode())->toBe(42);
        });

        it('creates exception with previous exception', function () {
            $previous = new Exception('Previous error');
            $exception = new NoRepoContextException('Error', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });
    });

    describe('inheritance', function () {
        it('extends RuntimeException', function () {
            $exception = new NoRepoContextException;

            expect($exception)->toBeInstanceOf(RuntimeException::class);
        });
    });
});
