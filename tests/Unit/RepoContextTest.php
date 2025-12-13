<?php

use ConduitUi\GitHubConnector\Connector;
use ConduitUi\GitHubConnector\Exceptions\InvalidRepositoryException;
use ConduitUi\GitHubConnector\Exceptions\NoRepoContextException;
use ConduitUi\GitHubConnector\ValueObjects\Repository;

beforeEach(function () {
    // Reset static state between tests
    Connector::clearRepo();
});

describe('Repository Context', function () {
    describe('setting context', function () {
        it('can set repo context with string', function () {
            Connector::forRepo('owner/repo');

            expect(Connector::repo())->toBeInstanceOf(Repository::class)
                ->and(Connector::repo()->toString())->toBe('owner/repo');
        });

        it('can set repo context with Repository instance', function () {
            $repo = new Repository('owner', 'repo');
            Connector::forRepo($repo);

            expect(Connector::repo())->toBe($repo);
        });

        it('can switch repo context', function () {
            Connector::forRepo('owner/first-repo');
            expect(Connector::repo()->toString())->toBe('owner/first-repo');

            Connector::forRepo('owner/second-repo');
            expect(Connector::repo()->toString())->toBe('owner/second-repo');
        });

        it('can clear repo context', function () {
            Connector::forRepo('owner/repo');
            expect(Connector::repo())->not->toBeNull();

            Connector::clearRepo();
            expect(Connector::repo())->toBeNull();
        });
    });

    describe('retrieving context', function () {
        it('returns null when no repo context is set', function () {
            expect(Connector::repo())->toBeNull();
        });

        it('requireRepo returns Repository instance', function () {
            Connector::forRepo('owner/repo');

            $repo = Connector::requireRepo();

            expect($repo)->toBeInstanceOf(Repository::class)
                ->and($repo->owner)->toBe('owner')
                ->and($repo->name)->toBe('repo');
        });

        it('can get owner from repo context', function () {
            Connector::forRepo('conduit-ui/connector');

            expect(Connector::owner())->toBe('conduit-ui');
        });

        it('can get repo name from repo context', function () {
            Connector::forRepo('conduit-ui/connector');

            expect(Connector::repoName())->toBe('connector');
        });

        it('handles repos with valid special characters', function () {
            Connector::forRepo('my-org/repo_name.v2');

            expect(Connector::owner())->toBe('my-org')
                ->and(Connector::repoName())->toBe('repo_name.v2');
        });
    });

    describe('missing context exceptions', function () {
        it('throws exception when requiring repo without context', function () {
            Connector::requireRepo();
        })->throws(NoRepoContextException::class, 'No repository context set. Call Connector::forRepo() first.');

        it('throws when getting owner without context', function () {
            Connector::owner();
        })->throws(NoRepoContextException::class);

        it('throws when getting repoName without context', function () {
            Connector::repoName();
        })->throws(NoRepoContextException::class);
    });

    describe('validation through Value Object', function () {
        it('throws InvalidRepositoryException for invalid format', function () {
            Connector::forRepo('invalid-format');
        })->throws(InvalidRepositoryException::class);

        it('throws InvalidRepositoryException for empty owner', function () {
            Connector::forRepo('/repo');
        })->throws(InvalidRepositoryException::class);

        it('throws InvalidRepositoryException for empty repo', function () {
            Connector::forRepo('owner/');
        })->throws(InvalidRepositoryException::class);

        it('throws InvalidRepositoryException for invalid characters', function () {
            Connector::forRepo('owner/<script>');
        })->throws(InvalidRepositoryException::class);

        it('validates repo format through Value Object', function () {
            Connector::forRepo('-invalid/repo');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a hyphen');
    });
});
