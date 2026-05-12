# Contributing to Employee Time Tracking

Thank you for your interest in contributing! This document provides guidelines to help you get started.

## How to Contribute

### Reporting Bugs

1. Check the [existing issues](https://github.com/pvernocchi/employee-time-tracking/issues) to avoid duplicates
2. Open a new issue with a clear title and description
3. Include steps to reproduce, expected behavior, and actual behavior
4. Mention your PHP version, MySQL version, and server environment

### Suggesting Features

Open an issue with the **feature request** label. Describe the use case and how the feature would benefit users.

### Submitting Changes

1. Fork the repository
2. Create a feature branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Make your changes following the coding standards below
4. Test your changes locally
5. Commit with clear, descriptive messages
6. Push to your fork and open a pull request

## Development Setup

### Prerequisites

- PHP 8.1+
- MySQL 5.7+
- Apache with `mod_rewrite`
- Composer

### Local Environment

```bash
git clone https://github.com/your-fork/employee-time-tracking.git
cd employee-time-tracking
composer install
cp config/config.example.php config/config.php
# Edit config/config.php with your local database credentials
# Import database/schema.sql into your MySQL instance
# Point your local web server to the public/ directory
```

### Validation

Run PHP syntax checks across the codebase:

```bash
find src/ public/ config/ -name "*.php" -exec php -l {} \;
```

## Coding Standards

- **PHP 8.1+** — use modern PHP features (typed properties, named arguments, match expressions, etc.)
- **PSR-4 autoloading** — all classes live under the `App\` namespace in `src/`
- **No external frameworks** — keep the vanilla PHP approach
- **Prepared statements** — always use parameterized queries for database access
- **Output escaping** — escape all user-generated content in views
- **CSRF protection** — include CSRF tokens on all forms

## Project Structure

- `src/Controllers/` — request handlers
- `src/Core/` — framework-level services (Router, Database, Auth, I18n, etc.)
- `src/Views/` — server-rendered PHP templates
- `src/Lang/` — translation files
- `database/migrations/` — incremental schema migrations
- `public/` — web root with front controller and static assets

## Pull Request Guidelines

- Keep PRs focused on a single concern
- Ensure PHP syntax is valid (`php -l`) for all changed files
- Update translations in `src/Lang/` if you add or change user-facing strings
- Add database migrations in `database/migrations/` for schema changes — do not modify `schema.sql` directly
- Test with multiple user roles (admin, manager, employee, inspector) if your changes affect access control

## License

By contributing, you agree that your contributions will be licensed under the [GNU General Public License v3.0](LICENSE).
