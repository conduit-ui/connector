# Connector - GitHub API Without the Guesswork

[![Latest Version on Packagist](https://img.shields.io/packagist/v/conduit-ui/connector.svg?style=flat-square)](https://packagist.org/packages/conduit-ui/connector)
[![Total Downloads](https://img.shields.io/packagist/dt/conduit-ui/connector.svg?style=flat-square)](https://packagist.org/packages/conduit-ui/connector)
[![CI](https://img.shields.io/github/actions/workflow/status/conduit-ui/connector/tests.yml?branch=master&label=CI&style=flat-square)](https://github.com/conduit-ui/connector/actions)
[![License](https://img.shields.io/packagist/l/conduit-ui/connector.svg?style=flat-square)](https://packagist.org/packages/conduit-ui/connector)

Stop wrestling with GitHub API authentication and error handling. Start with a clean HTTP transport layer that maps GitHub's API responses to typed exceptions automatically.

**Perfect for:** Building GitHub integrations, creating developer tools, automating repository workflows

## Quick Start

```bash
composer require conduit-ui/connector
```

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

## Related Packages

The conduit-ui ecosystem builds on this connector:

- **[conduit-ui/know](https://github.com/conduit-ui/know)** - Domain knowledge for AI agents (how/why/what/remember API)

More packages coming soon.

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
