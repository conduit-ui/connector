# Connector - GitHub API Without the Guesswork

[![Latest Version](https://img.shields.io/packagist/v/conduit-ui/connector.svg)](https://packagist.org/packages/conduit-ui/connector)
[![Total Downloads](https://img.shields.io/packagist/dt/conduit-ui/connector.svg)](https://packagist.org/packages/conduit-ui/connector)
[![PHP Version](https://img.shields.io/packagist/php-v/conduit-ui/connector.svg)](https://packagist.org/packages/conduit-ui/connector)
[![Tests](https://github.com/conduit-ui/connector/actions/workflows/gate.yml/badge.svg)](https://github.com/conduit-ui/connector/actions/workflows/gate.yml)
[![License](https://img.shields.io/github/license/conduit-ui/connector.svg)](LICENSE)

Stop wrestling with GitHub API authentication and error handling. Start with a clean HTTP transport layer that maps GitHub's API responses to typed exceptions automatically.

**Perfect for:** Building GitHub integrations, creating developer tools, automating repository workflows

## Installation

```bash
composer require conduit-ui/connector
```

## Quick Start

```php
use ConduitUi\GitHubConnector\Connector;

$connector = new Connector('ghp_your_token_here');

// Make requests - exceptions are typed automatically
try {
    $repos = $connector->send($getUserReposRequest);
    $newRepo = $connector->send($createRepoRequest);
} catch (GitHubRateLimitException $e) {
    // Handle rate limits with context
} catch (GitHubAuthException $e) {
    // Handle auth failures
}
```

## Features

- **Token authentication** - Pass your GitHub token, done
- **Automatic exception mapping** - 401s become `GitHubAuthException`, 403s become `GitHubForbiddenException`, 404s become `GitHubResourceNotFoundException`
- **Rate limit awareness** - Headers exposed, limits tracked
- **Built on Saloon** - Full power of Saloon's HTTP client underneath
- **GitHub API v3 compatibility** - API version headers set correctly
- **Zero opinions on requests** - Bring your own Request objects or use with higher-level SDKs

## Why This Exists

GitHub's API returns generic HTTP errors. Your application needs domain-specific exceptions. This package bridges that gap without forcing you into a full SDK.

The connector is the foundation - it handles authentication and error translation. Build your own request layer on top, or use it with conduit-ui's higher-level packages.

## Usage

### Basic Authentication

```php
$connector = new Connector('ghp_your_token_here');
```

### Using with Saloon Requests

```php
use Saloon\Http\Request;
use Saloon\Enums\Method;

class GetUserRepos extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/user/repos';
    }
}

$response = $connector->send(new GetUserRepos());
$repos = $response->json();
```

### Exception Handling

All GitHub HTTP errors are mapped to typed exceptions:

```php
use ConduitUi\GitHubConnector\Exceptions\{
    GitHubAuthException,           // 401 Unauthorized
    GitHubForbiddenException,      // 403 Forbidden
    GitHubResourceNotFoundException, // 404 Not Found
    GitHubValidationException,     // 422 Validation Failed
    GitHubRateLimitException,      // 429 Rate Limited
    GitHubServerException          // 500+ Server Errors
};

try {
    $response = $connector->send($request);
} catch (GitHubRateLimitException $e) {
    // Check rate limit headers
    $resetAt = $e->getResponse()->header('X-RateLimit-Reset');
    sleep($resetAt - time());
    // Retry...
} catch (GitHubResourceNotFoundException $e) {
    // Handle missing resource
} catch (GitHubException $e) {
    // Catch-all for any GitHub API error
}
```

### Rate Limit Headers

GitHub's rate limit headers are automatically included in responses:

```php
$response = $connector->send($request);

$remaining = $response->header('X-RateLimit-Remaining');
$resetAt = $response->header('X-RateLimit-Reset');
```

## Repository Context

The connector maintains a static repository context that ecosystem packages can inherit. Set once, use everywhere.

### Setting Context

```php
use ConduitUi\GitHubConnector\Connector;

// Set the repo context once
Connector::forRepo('owner/repo');

// All ecosystem packages now inherit this context
Issue::all();           // No repo arg needed
PullRequests::open();   // No repo arg needed
Commit::latest();       // No repo arg needed
```

### Accessing Context

```php
// Get the full repo string (nullable)
Connector::repo();       // 'owner/repo' or null

// Get the full repo string (throws if not set)
Connector::requireRepo(); // 'owner/repo' or throws NoRepoContextException

// Get individual parts
Connector::owner();      // 'owner'
Connector::repoName();   // 'repo'
```

### Switching Context

```php
// Work on first repo
Connector::forRepo('acme/api');
Issue::all(); // Issues from acme/api

// Switch to different repo
Connector::forRepo('acme/web');
Issue::all(); // Now issues from acme/web

// Clear context entirely
Connector::clearRepo();
Connector::repo(); // null
```

### Error Handling

```php
use ConduitUi\GitHubConnector\Exceptions\NoRepoContextException;

try {
    // Throws if no context set
    $owner = Connector::owner();
} catch (NoRepoContextException $e) {
    // "No repository context set. Call Connector::forRepo() first."
}

// Or check first
if (Connector::repo() !== null) {
    $owner = Connector::owner();
}
```

### Using in Ecosystem Packages

Packages in the conduit-ui ecosystem can delegate to the connector:

```php
// In your package (e.g., conduit-ui/issue)
class Issue
{
    public static function forRepo(string $repository): void
    {
        Connector::forRepo($repository);
    }

    public static function all(): Collection
    {
        $owner = Connector::owner();
        $repo = Connector::repoName();

        // Fetch issues using inherited context...
    }
}
```

This creates a unified experience where any package can set or use the context:

```php
// User can set context via any package
Issue::forRepo('owner/repo');

// Or directly on connector
Connector::forRepo('owner/repo');

// Either way, all packages inherit it
PullRequests::open()->get();
Commit::since(now()->subWeek());
```

## Related Packages

The conduit-ui ecosystem builds on this connector:

- **[conduit-ui/issue](https://github.com/conduit-ui/issue)** - GitHub issue management
- **[conduit-ui/pr](https://github.com/conduit-ui/pr)** - Pull request operations
- **[conduit-ui/repo](https://github.com/conduit-ui/repo)** - Repository operations
- **[conduit-ui/commit](https://github.com/conduit-ui/commit)** - Commit management
- **[conduit-ui/action](https://github.com/conduit-ui/action)** - GitHub Actions operations
- **[conduit-ui/know](https://github.com/conduit-ui/know)** - Domain knowledge for AI agents

## Requirements

- PHP 8.2 or higher
- Saloon HTTP client 3.10+

## Testing

```bash
composer test
```

## Support

**Enterprise support available** - Need SLA guarantees, custom integrations, or priority bug fixes? Email jordan@partridge.rocks

**Community** - Open an issue on [GitHub](https://github.com/conduit-ui/connector/issues) or contribute a PR.

## License

MIT License. See [LICENSE](LICENSE) for details.
