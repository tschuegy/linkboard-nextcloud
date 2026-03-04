# LinkBoard – Nextcloud App

A beautiful, customizable service dashboard for your homelab, inspired by [Gethomepage](https://gethomepage.dev).

![Nextcloud](https://img.shields.io/badge/Nextcloud-30--33-blue)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple)
![License](https://img.shields.io/badge/License-AGPL--3.0-green)

## Features

- 🏠 Personal dashboard with service tiles grouped by category
- 🎨 Dark/Light mode (follows Nextcloud theme or custom setting)
- 🖼️ Flexible icon system – upload local images, use URLs, or MDI icons
- ✏️ Inline editing with sidebar editor
- 🔍 Quick search across all services
- 📋 YAML & JSON import/export (Phase 2)
- 🩺 Service health checks with status indicators (Phase 2)
- 🔀 Drag & drop sorting (Phase 3)
- 📊 Widget system for live data from external APIs (Phase 4)

## Requirements

- Nextcloud 30, 31, 32, or 33
- PHP 8.1 – 8.4

## Development Setup

### Prerequisites

- Node.js 20+
- npm / pnpm
- PHP 8.1+
- Composer
- A Nextcloud development instance

### Installation

```bash
# Clone into your Nextcloud apps directory
cd /path/to/nextcloud/apps/
git clone https://github.com/tschuegy/linkboard-nextcloud.git
cd linkboard

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Build the frontend
npm run build

# Enable the app
cd /path/to/nextcloud/
php occ app:enable linkboard
```

### Development

```bash
# Watch mode (auto-rebuild on changes)
npm run watch

# Production build
npm run build

# Lint
npm run lint
npm run lint:fix
```

## Project Structure

```
linkboard/
├── appinfo/           # App metadata, routes
├── lib/               # PHP backend
│   ├── AppInfo/       # Bootstrap
│   ├── Controller/    # REST API controllers
│   ├── Db/            # Entity & mapper classes
│   ├── Service/       # Business logic
│   └── Migration/     # Database migrations
├── src/               # Vue.js frontend
│   ├── components/    # Vue components
│   ├── store/         # Pinia state management
│   └── services/      # API client
├── css/               # Global styles
├── img/               # App icon
└── templates/         # PHP templates
```

## API Overview

All endpoints under `/apps/linkboard/api/v1/`:

| Endpoint | Methods | Description |
|----------|---------|-------------|
| `/dashboard` | GET | Full dashboard data (categories + services + settings) |
| `/categories` | GET, POST | List/create categories |
| `/categories/{id}` | GET, PUT, DELETE | Single category CRUD |
| `/categories/reorder` | PUT | Reorder categories |
| `/services` | GET, POST | List/create services |
| `/services/{id}` | GET, PUT, DELETE | Single service CRUD |
| `/services/reorder` | PUT | Reorder services |
| `/services/{id}/move/{catId}` | PUT | Move service to category |
| `/settings` | GET, PUT | User settings |
| `/icons` | GET, POST | List/upload icons |
| `/icons/{filename}` | GET, DELETE | Serve/delete icon |

## License

AGPL-3.0-or-later
