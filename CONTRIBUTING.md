# Contributing to LinkBoard

Thank you for your interest in contributing to LinkBoard! This guide will help you get started.

## Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## Getting Started

See the [README](README.md) for development setup instructions, including prerequisites and build commands.

### Dev Commands

| Command | Description |
|---------|-------------|
| `npm run build` | Build production assets |
| `npm run watch` | Watch mode for development |
| `npm run lint` | Run linter |
| `npm run lint:fix` | Run linter with auto-fix |

## How to Contribute

### Reporting Bugs

Please use the [Bug Report](https://github.com/tschuegy/linkboard-nextcloud/issues/new?template=bug_report.yml) issue template. Include your Nextcloud version, LinkBoard version, PHP version, and steps to reproduce.

### Suggesting Features

Use the [Feature Request](https://github.com/tschuegy/linkboard-nextcloud/issues/new?template=feature_request.yml) issue template to propose new features or widget ideas.

### Submitting Pull Requests

1. Fork the repository and create a branch from `main`
2. Make your changes following the coding standards below
3. Test your changes manually in a Nextcloud instance
4. Update documentation if needed (README.md, WIDGETS.md, CHANGELOG.md)
5. Open a pull request against `main`

## Coding Standards

### PHP

- Controllers extend `ApiController` with `#[NoAdminRequired]` attributes
- Use constructor dependency injection with `?string $userId`
- Follow existing patterns in `lib/Controller/`, `lib/Service/`, and `lib/Db/`

### Vue.js

- Use Vue 2 **Options API** (not Composition API)
- Use `var` instead of `const`/`let` in component methods (project convention)
- Frontend state management uses Pinia stores

### Translations

All user-facing strings must use `t('linkboard', '...')`. When adding or modifying strings, update **all** files in `l10n/` (both `.json` and `.js` formats). German translations (`de`, `de_DE`) must be proper translations; other languages can use the English key as a placeholder.

## Database Migrations

- Each migration requires a version bump of `+0.0.1` in `appinfo/info.xml`
- Nextcloud only runs migrations when the version in `info.xml` is higher than the last installed version
- Test migrations with: `sudo -u www-data php /var/www/nextcloud/occ upgrade`

## Widget Development

Widgets follow the pattern in `lib/Widget/Widgets/`. Each widget extends `AbstractWidget` and implements:

- `getId()` / `getLabel()` — identifier and display name
- `getConfigFields()` / `getAllowedFields()` / `getFieldLabels()` — configuration schema
- `buildRequests()` — outbound HTTP request(s)
- `mapResponse()` — transform API response into display data

Register new widgets in `lib/Widget/WidgetRegistry.php` and document them in [WIDGETS.md](WIDGETS.md).

## License

By contributing, you agree that your contributions will be licensed under the [AGPL-3.0-or-later](LICENSE) license.
