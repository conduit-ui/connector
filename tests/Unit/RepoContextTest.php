<?php

use ConduitUi\GitHubConnector\Connector;
use ConduitUi\GitHubConnector\Exceptions\NoRepoContextException;

beforeEach(function () {
    // Reset static state between tests
    Connector::clearRepo();
});

it('can set repo context with forRepo', function () {
    Connector::forRepo('owner/repo');

    expect(Connector::repo())->toBe('owner/repo');
});

it('returns null when no repo context is set', function () {
    expect(Connector::repo())->toBeNull();
});

it('throws exception when requiring repo without context', function () {
    Connector::requireRepo();
})->throws(NoRepoContextException::class, 'No repository context set. Call Connector::forRepo() first.');

it('can get owner from repo context', function () {
    Connector::forRepo('conduit-ui/connector');

    expect(Connector::owner())->toBe('conduit-ui');
});

it('can get repo name from repo context', function () {
    Connector::forRepo('conduit-ui/connector');

    expect(Connector::repoName())->toBe('connector');
});

it('throws when getting owner without context', function () {
    Connector::owner();
})->throws(NoRepoContextException::class);

it('throws when getting repoName without context', function () {
    Connector::repoName();
})->throws(NoRepoContextException::class);

it('can switch repo context', function () {
    Connector::forRepo('owner/first-repo');
    expect(Connector::repo())->toBe('owner/first-repo');

    Connector::forRepo('owner/second-repo');
    expect(Connector::repo())->toBe('owner/second-repo');
});

it('can clear repo context', function () {
    Connector::forRepo('owner/repo');
    expect(Connector::repo())->toBe('owner/repo');

    Connector::clearRepo();
    expect(Connector::repo())->toBeNull();
});

it('validates repo format on forRepo', function () {
    Connector::forRepo('invalid-format');
})->throws(InvalidArgumentException::class, 'Repository must be in owner/repo format');

it('handles repos with slashes in name', function () {
    Connector::forRepo('owner/repo-with-dash');

    expect(Connector::owner())->toBe('owner')
        ->and(Connector::repoName())->toBe('repo-with-dash');
});
