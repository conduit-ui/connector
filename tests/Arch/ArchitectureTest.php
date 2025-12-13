<?php

declare(strict_types=1);

describe('Architecture', function () {
    arch('value objects are final and readonly')
        ->expect('ConduitUi\GitHubConnector\ValueObjects')
        ->toBeFinal()
        ->toBeReadonly();

    arch('exceptions extend base exception classes')
        ->expect('ConduitUi\GitHubConnector\Exceptions')
        ->toExtend(Exception::class);

    arch('source code uses strict types')
        ->expect('ConduitUi\GitHubConnector')
        ->toUseStrictTypes();

    arch('contracts are interfaces')
        ->expect('ConduitUi\GitHubConnector\Contracts')
        ->toBeInterfaces();

    arch('no debugging statements in production code')
        ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
        ->not->toBeUsed();

    arch('exceptions are suffixed with Exception')
        ->expect('ConduitUi\GitHubConnector\Exceptions')
        ->toHaveSuffix('Exception');

    arch('value objects implement Stringable')
        ->expect('ConduitUi\GitHubConnector\ValueObjects\Repository')
        ->toImplement(Stringable::class);
});
