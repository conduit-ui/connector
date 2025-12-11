# GitHub Connector

Core GitHub API connector with authentication, built on Saloon HTTP client.

## Installation

```bash
composer require conduit-ui/github-connector
```

## Usage

```php
use ConduitUi\GitHubConnector\Connector;

$connector = new Connector('your-token');

// Make raw HTTP requests
$repos = $connector->get('/user/repos');
$newRepo = $connector->post('/user/repos', ['name' => 'new-repo']);
```

## Features

- Token-based authentication
- Full HTTP method support (GET, POST, PUT, PATCH, DELETE)
- Built on Saloon HTTP client
- GitHub API v3 compatibility
- PSR-compliant