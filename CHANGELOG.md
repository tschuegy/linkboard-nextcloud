# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.2.0 – 2026-03-07

### Added
- System Resources widget displaying CPU, memory, disk usage, uptime, and CPU temperature with progress bars (new `resources` category type)
- Category spacers with multiple decorative styles (solid, dashed, dotted, dots, stars, diamonds, arrows, fade, wave)
- Card background options: Glass, Solid, Flat, Transparent
- Version check in footer with optional update notifications from GitHub
- MDI inline SVG rendering for Material Design Icons (replaces letter fallbacks)
- GitHub Sponsors badge in README

### Changed
- Consolidated database migrations (merged into single v1.4 migration adding `parent_id`, `type`, `config` columns)
- Removed "ungroup" drop zone from dashboard (simplification)
- Dialogs use `:open` prop pattern instead of `v-if`

## 1.1.0 – 2026-03-05

### Added
- Category nesting with drag-and-drop grouping

## 1.0.0 – 2026-03-01

### Added
- Personal service dashboard with category-based organization
- Drag & drop sorting for categories and services
- Service health checks with status indicators (dot/border styles)
- Widget system with 134 built-in widgets for live data from external APIs
- Custom icon upload (PNG, JPEG, SVG, WebP, GIF, ICO)
- Material Design Icon support (mdi-* prefix)
- URL-based icon support
- Dark/Light/Auto theme with customizable backgrounds and blur
- Configurable layout (columns, card styles, search bar visibility)
- YAML and JSON import/export (Gethomepage services.yaml compatible)
- Background job for periodic status checks
- Full internationalization (i18n) with 57 languages
- Keyboard shortcuts (/, E, R, Esc)
- Tab-based category grouping
- Collapsible category sections
- Quick search across all services
