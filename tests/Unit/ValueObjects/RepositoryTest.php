<?php

use ConduitUi\GitHubConnector\Exceptions\InvalidRepositoryException;
use ConduitUi\GitHubConnector\ValueObjects\Repository;

describe('Repository Value Object', function () {
    describe('creation', function () {
        it('can be created from owner and name', function () {
            $repo = new Repository('laravel', 'framework');

            expect($repo->owner)->toBe('laravel')
                ->and($repo->name)->toBe('framework');
        });

        it('can be created from string', function () {
            $repo = Repository::fromString('laravel/framework');

            expect($repo->owner)->toBe('laravel')
                ->and($repo->name)->toBe('framework');
        });

        it('preserves case', function () {
            $repo = Repository::fromString('Laravel/Framework');

            expect($repo->owner)->toBe('Laravel')
                ->and($repo->name)->toBe('Framework');
        });

        it('throws for string with multiple slashes since slashes are invalid in repo name', function () {
            Repository::fromString('owner/repo/with/slashes');
        })->throws(InvalidRepositoryException::class, 'contains invalid characters');
    });

    describe('string conversion', function () {
        it('converts to string correctly', function () {
            $repo = new Repository('laravel', 'framework');

            expect($repo->toString())->toBe('laravel/framework')
                ->and((string) $repo)->toBe('laravel/framework');
        });

        it('implements Stringable', function () {
            $repo = new Repository('owner', 'repo');

            expect($repo)->toBeInstanceOf(Stringable::class);
        });
    });

    describe('equality', function () {
        it('returns true for equal repositories', function () {
            $repo1 = new Repository('owner', 'repo');
            $repo2 = new Repository('owner', 'repo');

            expect($repo1->equals($repo2))->toBeTrue();
        });

        it('returns false for different repo names', function () {
            $repo1 = new Repository('owner', 'repo');
            $repo2 = new Repository('owner', 'other');

            expect($repo1->equals($repo2))->toBeFalse();
        });

        it('returns false for different owners', function () {
            $repo1 = new Repository('owner', 'repo');
            $repo2 = new Repository('other', 'repo');

            expect($repo1->equals($repo2))->toBeFalse();
        });
    });

    describe('format validation', function () {
        it('throws exception for missing slash', function () {
            Repository::fromString('invalid-format');
        })->throws(InvalidRepositoryException::class, "must contain a '/' separator");

        it('throws exception for empty owner', function () {
            Repository::fromString('/repo');
        })->throws(InvalidRepositoryException::class, 'owner cannot be empty');

        it('throws exception for empty repo name', function () {
            Repository::fromString('owner/');
        })->throws(InvalidRepositoryException::class, 'name cannot be empty');
    });

    describe('hyphen validation', function () {
        it('throws exception for owner starting with hyphen', function () {
            new Repository('-owner', 'repo');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a hyphen');

        it('throws exception for owner ending with hyphen', function () {
            new Repository('owner-', 'repo');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a hyphen');

        it('throws exception for repo name starting with hyphen', function () {
            new Repository('owner', '-repo');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a hyphen');

        it('throws exception for repo name ending with hyphen', function () {
            new Repository('owner', 'repo-');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a hyphen');
    });

    describe('period validation', function () {
        it('throws exception for owner starting with period', function () {
            new Repository('.owner', 'repo');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a period');

        it('throws exception for owner ending with period', function () {
            new Repository('owner.', 'repo');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a period');

        it('throws exception for repo name starting with period', function () {
            new Repository('owner', '.repo');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a period');

        it('throws exception for repo name ending with period', function () {
            new Repository('owner', 'repo.');
        })->throws(InvalidRepositoryException::class, 'cannot start or end with a period');
    });

    describe('character validation', function () {
        it('throws exception for invalid characters in owner', function () {
            new Repository('owner@name', 'repo');
        })->throws(InvalidRepositoryException::class, 'contains invalid characters');

        it('throws exception for invalid characters in repo name', function () {
            new Repository('owner', 'repo<script>');
        })->throws(InvalidRepositoryException::class, 'contains invalid characters');
    });

    describe('valid inputs', function () {
        it('allows valid characters in owner', function () {
            $repo = new Repository('my-org_name.test', 'repo');

            expect($repo->owner)->toBe('my-org_name.test');
        });

        it('allows valid characters in repo name', function () {
            $repo = new Repository('owner', 'my-repo_name.v2');

            expect($repo->name)->toBe('my-repo_name.v2');
        });

        it('allows single character owner', function () {
            $repo = new Repository('a', 'repo');

            expect($repo->owner)->toBe('a');
        });

        it('allows single character repo name', function () {
            $repo = new Repository('owner', 'r');

            expect($repo->name)->toBe('r');
        });

        it('allows numeric characters', function () {
            $repo = new Repository('user123', 'project456');

            expect($repo->owner)->toBe('user123')
                ->and($repo->name)->toBe('project456');
        });
    });

    describe('exception details', function () {
        it('provides recovery suggestion in exception', function () {
            try {
                Repository::fromString('invalid');
            } catch (InvalidRepositoryException $e) {
                expect($e->getRecoverySuggestion())->toContain("'owner/repo' format");
            }
        });

        it('provides repository in exception', function () {
            try {
                Repository::fromString('invalid');
            } catch (InvalidRepositoryException $e) {
                expect($e->getRepository())->toBe('invalid');
            }
        });
    });
});
