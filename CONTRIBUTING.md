# Contributing to Conduit-UI Connector

Thank you for considering contributing to the Conduit-UI ecosystem! This guide will help you get started.

## Code of Conduct

Be respectful, constructive, and professional. We're all here to build better tools for developers and agents.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues. When you create a bug report, include as many details as possible:

- Use a clear and descriptive title
- Describe the exact steps to reproduce the problem
- Provide specific examples to demonstrate the steps
- Describe the behavior you observed and what you expected
- Include code samples and error messages
- Note your PHP version and dependency versions

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- Use a clear and descriptive title
- Provide a detailed description of the suggested enhancement
- Explain why this enhancement would be useful
- List examples of how it would be used

### Pull Requests

1. Fork the repository and create your branch from `main`
2. Install dependencies: `composer install`
3. Make your changes following our coding standards
4. Add tests for any new functionality
5. Ensure all tests pass: `composer test`
6. Run static analysis: `composer analyse`
7. Format your code: `composer format`
8. Commit your changes with a descriptive message
9. Push to your fork and submit a pull request

## Development Setup

```bash
# Clone your fork
git clone git@github.com:your-username/connector.git
cd connector

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Format code
composer format
```

## Coding Standards

### PHP Standards

- PHP 8.2+ features are encouraged
- Use strict types: `declare(strict_types=1);`
- Follow PSR-12 coding standards
- Use Laravel Pint for formatting
- Maintain PHPStan level 8 compliance

### Testing Standards

- Write tests using Pest
- Use `describe()` and `it()` syntax
- Aim for 100% code coverage
- Test edge cases and error conditions
- Mock external API calls

### Example Test Structure

```php
describe('Connector', function (): void {
    it('authenticates with GitHub token', function (): void {
        $connector = new Connector('ghp_token');

        expect($connector)->toBeInstanceOf(Connector::class);
    });

    it('throws exception for invalid token', function (): void {
        $connector = new Connector('invalid');

        // Test exception throwing
    })->throws(GitHubAuthException::class);
});
```

### Documentation Standards

- Update README.md for user-facing changes
- Update CHANGELOG.md following Keep a Changelog format
- Add PHPDoc blocks for public methods
- Include code examples in documentation
- Use clear, concise language

### Commit Message Format

```
feat: add repository context management
fix: correct rate limit header parsing
docs: update README with new examples
test: add coverage for exception handling
refactor: simplify connector initialization
```

Prefixes:
- `feat:` New features
- `fix:` Bug fixes
- `docs:` Documentation changes
- `test:` Test additions or changes
- `refactor:` Code refactoring
- `style:` Formatting changes
- `chore:` Build process or tooling changes

## Testing

All contributions must include tests. Run the full test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Static Analysis

Code must pass PHPStan level 8:

```bash
composer analyse
```

## Code Formatting

Use Laravel Pint to format your code:

```bash
composer format
```

## Questions?

Feel free to open an issue for questions or reach out to the maintainers.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for contributing to the Conduit-UI ecosystem!**
