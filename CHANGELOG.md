# Changelog

All notable changes to `conduit-ui/connector` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Ecosystem package consistency audit
- Comprehensive documentation standards
- Missing package files (LICENSE, CHANGELOG, CONTRIBUTING)

## [1.0.0] - 2024-12-19

### Added
- Initial release
- GitHub API connector with token authentication
- Automatic exception mapping for GitHub HTTP errors
- Repository context management
- Rate limit awareness
- Built on Saloon HTTP client
- Typed exceptions for all GitHub error states
- Static repository context for ecosystem packages

### Exception Types
- `GitHubAuthException` (401 Unauthorized)
- `GitHubForbiddenException` (403 Forbidden)
- `GitHubResourceNotFoundException` (404 Not Found)
- `GitHubValidationException` (422 Validation Failed)
- `GitHubRateLimitException` (429 Rate Limited)
- `GitHubServerException` (500+ Server Errors)

### Features
- Token authentication
- Automatic exception mapping
- Rate limit header exposure
- Repository context inheritance
- Zero-opinion request layer
- GitHub API v3 compatibility

[Unreleased]: https://github.com/conduit-ui/connector/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/conduit-ui/connector/releases/tag/v1.0.0
