<?php

declare(strict_types=1);

describe('Architecture', function () {
    describe('value objects', function () {
        arch('are final and readonly')
            ->expect('ConduitUi\GitHubConnector\ValueObjects')
            ->toBeFinal()
            ->toBeReadonly();

        arch('implement Stringable')
            ->expect('ConduitUi\GitHubConnector\ValueObjects\Repository')
            ->toImplement(Stringable::class);
    });

    describe('exceptions', function () {
        arch('extend base exception classes')
            ->expect('ConduitUi\GitHubConnector\Exceptions')
            ->toExtend(Exception::class);

        arch('are suffixed with Exception')
            ->expect('ConduitUi\GitHubConnector\Exceptions')
            ->toHaveSuffix('Exception');

        arch('do not depend on Connector')
            ->expect('ConduitUi\GitHubConnector\Exceptions')
            ->not->toUse('ConduitUi\GitHubConnector\Connector');
    });

    describe('contracts', function () {
        arch('are interfaces')
            ->expect('ConduitUi\GitHubConnector\Contracts')
            ->toBeInterfaces();
    });

    describe('code quality', function () {
        arch('source code uses strict types')
            ->expect('ConduitUi\GitHubConnector')
            ->toUseStrictTypes();

        arch('no debugging statements in production code')
            ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
            ->not->toBeUsed();

        arch('no exit or die calls')
            ->expect(['exit', 'die'])
            ->not->toBeUsed();
    });

    describe('dependency direction', function () {
        arch('value objects do not depend on exceptions')
            ->expect('ConduitUi\GitHubConnector\ValueObjects')
            ->toOnlyUse([
                'ConduitUi\GitHubConnector\Exceptions\InvalidRepositoryException',
                'Stringable',
            ]);

        arch('contracts do not depend on implementations')
            ->expect('ConduitUi\GitHubConnector\Contracts')
            ->not->toUse('ConduitUi\GitHubConnector\Connector');
    });
});
