<?php
declare(strict_types=1);

/**
 * LinkBoard - Routes
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
    'routes' => [
        // ── Page (serves the Vue.js SPA) ──────────────────────
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        // ── Categories API ────────────────────────────────────
        ['name' => 'category_api#index',   'url' => '/api/v1/categories',             'verb' => 'GET'],
        ['name' => 'category_api#create',  'url' => '/api/v1/categories',             'verb' => 'POST'],
        ['name' => 'category_api#reorder', 'url' => '/api/v1/categories/reorder',     'verb' => 'POST'],
        ['name' => 'category_api#show',    'url' => '/api/v1/categories/{id}',    'verb' => 'GET',    'requirements' => ['id' => '\d+']],
        ['name' => 'category_api#update',  'url' => '/api/v1/categories/{id}',    'verb' => 'PUT',    'requirements' => ['id' => '\d+']],
        ['name' => 'category_api#destroy', 'url' => '/api/v1/categories/{id}',    'verb' => 'DELETE', 'requirements' => ['id' => '\d+']],


        // ── Services API ──────────────────────────────────────
        ['name' => 'service_api#index',          'url' => '/api/v1/services',                          'verb' => 'GET'],
        ['name' => 'service_api#byCategory',     'url' => '/api/v1/categories/{categoryId}/services',  'verb' => 'GET'],
        ['name' => 'service_api#create',         'url' => '/api/v1/services',                          'verb' => 'POST'],
        ['name' => 'service_api#reorder',        'url' => '/api/v1/services/reorder',                  'verb' => 'POST'],
        ['name' => 'service_api#show',     'url' => '/api/v1/services/{id}',      'verb' => 'GET',    'requirements' => ['id' => '\d+']],
        ['name' => 'service_api#update',   'url' => '/api/v1/services/{id}',      'verb' => 'PUT',    'requirements' => ['id' => '\d+']],
        ['name' => 'service_api#destroy',  'url' => '/api/v1/services/{id}',      'verb' => 'DELETE', 'requirements' => ['id' => '\d+']],
        ['name' => 'service_api#move',     'url' => '/api/v1/services/{id}/move/{newCategoryId}', 'verb' => 'PUT', 'requirements' => ['id' => '\d+', 'newCategoryId' => '\d+']],

        // ── Settings API ──────────────────────────────────────
        ['name' => 'settings_api#index',       'url' => '/api/v1/settings',        'verb' => 'GET'],
        ['name' => 'settings_api#updateAll',   'url' => '/api/v1/settings',        'verb' => 'PUT'],
        ['name' => 'settings_api#updateSingle','url' => '/api/v1/settings/{key}',  'verb' => 'PUT'],

        // ── Icons API ─────────────────────────────────────────
        ['name' => 'icon_api#index',    'url' => '/api/v1/icons',            'verb' => 'GET'],
        ['name' => 'icon_api#upload',   'url' => '/api/v1/icons/upload',     'verb' => 'POST'],
        ['name' => 'icon_api#destroy',  'url' => '/api/v1/icons/{filename}', 'verb' => 'DELETE'],
        ['name' => 'icon_api#serve',    'url' => '/api/v1/icons/{filename}', 'verb' => 'GET'],

        // ── Status API (Phase 2) ─────────────────────────────
        ['name' => 'status_api#index',    'url' => '/api/v1/status',            'verb' => 'GET'],
        ['name' => 'status_api#check',    'url' => '/api/v1/status/{id}/check', 'verb' => 'POST'],
        ['name' => 'status_api#checkAll', 'url' => '/api/v1/status/check-all',  'verb' => 'POST'],

        // ── Widget API ──────────────────────────────────────
        ['name' => 'widget_proxy#catalog',    'url' => '/api/v1/widgets/catalog',          'verb' => 'GET'],
        ['name' => 'widget_proxy#getAllData',  'url' => '/api/v1/widgets/data',             'verb' => 'GET'],
        ['name' => 'widget_proxy#getData',     'url' => '/api/v1/widgets/{serviceId}/data', 'verb' => 'GET', 'requirements' => ['serviceId' => '\d+']],

        // ── Import/Export API (Phase 2) ──────────────────────
        ['name' => 'import_export#exportJson',  'url' => '/api/v1/export/json',  'verb' => 'GET'],
        ['name' => 'import_export#exportYaml',  'url' => '/api/v1/export/yaml',  'verb' => 'GET'],
        ['name' => 'import_export#importJson',  'url' => '/api/v1/import/json',  'verb' => 'POST'],
        ['name' => 'import_export#importYaml',  'url' => '/api/v1/import/yaml',  'verb' => 'POST'],

        // ── Dashboard (full data endpoint) ────────────────────
        ['name' => 'dashboard_api#index', 'url' => '/api/v1/dashboard', 'verb' => 'GET'],
    ],
];
