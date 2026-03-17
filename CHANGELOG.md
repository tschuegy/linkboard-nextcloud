# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.5] – 2026-03-17

### Changed
- UniFi widget: added Controller Type selector (UniFi OS vs Legacy Controller) with correct API paths and cookie-based session auth
- Dropped support for Nextcloud 30/31 and PHP 8.1 (now requires Nextcloud 32+ and PHP 8.2+)

### Fixed
- Missing translations for Status History modal and Status Overview page (12 new strings in all 57 languages, German fully translated)
- Status label in StatusHistoryModal not using i18n (`Status` hardcoded instead of `t('linkboard', 'Status')`)
- Status badge in StatusOverviewPage showing raw status key instead of translated label (Online/Offline/Unknown)
- Period selector buttons missing focus-visible outline for keyboard accessibility

## [1.5.4] – 2026-03-16

### Fixed
- Migration `Version001009Date20260316000000` also calling `$result->free()` instead of `closeCursor()`, causing fresh installations to fail (same issue as v1.5.3 fix, but in the earlier migration)

## [1.5.3] – 2026-03-16

### Fixed
- Migration `Version001010Date20260317000000` calling `$result->free()` which does not exist on Nextcloud 33's `ResultAdapter`, causing `occ upgrade` to fail and leaving Nextcloud stuck in maintenance mode (replaced with `closeCursor()`)

## [1.5.2] – 2026-03-17

### Fixed
- Table names `linkboard_notif_channels` and `linkboard_status_history` exceeding Nextcloud 32 primary index name length limit (renamed to `linkboard_channels` and `linkboard_history`)

## 1.5.1 – 2026-03-16

### Fixed
- Table name `oc_linkboard_notification_channels` too long for Nextcloud 32 Oracle DB compatibility (renamed to `linkboard_notif_channels`)
- Status check retry with GET on 5xx responses from HEAD requests

## 1.5.0 – 2026-03-15

### Added
- Status history tracking – background job records status checks with response times in new `linkboard_status_history` table
- Status History Modal – click any service's status indicator to view response time charts
- Status Overview Page (`/status`) – dedicated page showing all monitored services with uptime%, failure counts, and charts
- Mini status history bars on service cards showing last ~10 status checks (configurable opacity in settings)
- Period-based history views (1h, 3h, 24h, 7d)
- Total failure counter per service in status cache
- New settings: "Show status history bars" toggle and "Status bar opacity" slider
- Widget warning display in widget containers

### Fixed
- Boolean NotNull columns breaking installation on Nextcloud 32 (Oracle constraint compatibility)

## 1.4.2 – 2026-03-10

### Fixed
- Fix database.xml validation for Nextcloud App Store (merge duplicate `<declaration>` blocks)

## 1.4.1 – 2026-03-09

### Added
- Per-service notification channel overrides: disable globally-enabled channels or enable globally-disabled channels on individual services

### Fixed
- Clear status cache when disabling status checks to prevent stale data on re-enable

## 1.4.0 – 2026-03-09

### Added
- External notification channels with 19 providers: Webhook, Discord, Slack, Telegram, Matrix, Microsoft Teams, Nextcloud Talk, Google Chat, Signal, Threema, CallMeBot, Home Assistant, Gotify, Ntfy, Pushover, Brevo, SendGrid, Resend, and E-Mail (SMTP)
- Provider registry system (following the widget pattern) for easy extensibility
- Nextcloud notifications toggle (enable/disable built-in notifications independently)
- Test button for each notification channel to verify configuration
- Per-channel enable/disable toggle

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
