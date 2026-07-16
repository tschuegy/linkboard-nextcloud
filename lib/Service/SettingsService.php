<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

use OCA\LinkBoard\Db\SettingMapper;

class SettingsService {

    private const ENUM_VALUES = [
        'theme' => ['auto', 'manual', 'light', 'dark'],
        'background_blur' => ['none', 'sm', 'md', 'lg', 'xl'],
        'max_columns' => ['2', '3', '4', '5', '6'],
        'card_style' => ['default', 'compact', 'minimal'],
        'card_background' => ['glass', 'solid', 'flat', 'transparent'],
        'status_style' => ['dot', 'basic'],
        'spacer_style' => [
            'solid', 'dashed', 'dotted', 'double', 'thin', 'heavy',
            'double-line', 'light-dashed', 'heavy-dashed', 'dots',
            'wave', 'stars', 'diamonds', 'fade', 'arrows',
        ],
        'notify_failures_threshold' => ['1', '2', '3', '5', '10'],
        'status_check_timeout' => ['100', '200', '500', '1000', '2000', '5000', '10000'],
        'font_color_mode' => ['auto', 'manual'],
    ];

    private const BOOLEAN_KEYS = [
        'show_search',
        'show_category_count',
        'check_for_updates',
        'notify_recovery',
        'notify_nextcloud',
        'status_checks_parallel',
        'show_status_bars',
    ];

    private const COLOR_KEYS = [
        'manual_color_title',
        'manual_color_category',
        'manual_color_service',
        'manual_color_description',
        'manual_color_widget_value',
        'manual_color_widget_label',
        'manual_color_card_bg',
        'manual_color_header_button',
    ];

    public function __construct(
        private SettingMapper $settingMapper,
    ) {
    }

    /**
     * Get all settings with defaults applied
     */
    public function getAll(string $userId): array {
        $stored = $this->settingMapper->getSettingsMap($userId);
        $settings = [];

        foreach (SettingMapper::DEFAULTS as $key => $default) {
            try {
                $settings[$key] = self::normalizeValue($key, $stored[$key] ?? $default);
            } catch (ValidationException) {
                $settings[$key] = $default;
            }
        }

        return $settings;
    }

    /**
     * Get a single setting value
     */
    public function get(string $key, string $userId): string {
        $settings = $this->getAll($userId);
        return $settings[$key] ?? '';
    }

    /**
     * Update a single setting
     */
    public function set(string $key, mixed $value, string $userId): string {
        $normalized = self::normalizeValue($key, $value);
        $this->settingMapper->setSetting($key, $normalized, $userId);
        return $normalized;
    }

    /**
     * Update multiple settings at once
     * @param array<string, mixed> $settings
            if (!is_string($key)) {
                throw new ValidationException('Invalid setting');
            }
     */
    public function setMultiple(array $settings, string $userId): void {
        $normalized = [];
        foreach ($settings as $key => $value) {
            $normalized[$key] = self::normalizeValue($key, $value);
        }

        foreach ($normalized as $key => $value) {
            $this->settingMapper->setSetting($key, $value, $userId);
        }
    }

    public static function isSupported(string $key): bool {
        return array_key_exists($key, SettingMapper::DEFAULTS);
    }

    /**
     * @throws ValidationException
     */
    public static function normalizeValue(string $key, mixed $value): string {
        if (!self::isSupported($key) || is_array($value) || is_object($value) || is_resource($value)) {
            throw new ValidationException('Invalid setting');
        }

        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if ($value === 1 || $value === '1' || $value === 'true') {
                return 'true';
            }
            if ($value === 0 || $value === '0' || $value === 'false') {
                return 'false';
            }
            throw new ValidationException('Invalid setting');
        }

        if ($value === null) {
            $value = '';
        }
        if (!is_scalar($value)) {
            throw new ValidationException('Invalid setting');
        }
        $value = (string)$value;

        if (isset(self::ENUM_VALUES[$key])) {
            if (!in_array($value, self::ENUM_VALUES[$key], true)) {
                throw new ValidationException('Invalid setting');
            }
            return $value;
        }

        if (in_array($key, self::COLOR_KEYS, true)) {
            if ($value !== '' && preg_match('/^#[0-9a-fA-F]{6}$/D', $value) !== 1) {
                throw new ValidationException('Invalid setting');
            }
            return $value;
        }

        if ($key === 'title') {
            if (strlen($value) > 512 || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
                throw new ValidationException('Invalid setting');
            }
            return $value;
        }

        if ($key === 'background_url') {
            return self::normalizeBackgroundUrl($value);
        }

        if ($key === 'status_bars_opacity') {
            if (!is_numeric($value)) {
                throw new ValidationException('Invalid setting');
            }
            $opacity = (float)$value;
            if (!is_finite($opacity) || $opacity < 0.2 || $opacity > 1.0) {
                throw new ValidationException('Invalid setting');
            }
            return rtrim(rtrim(number_format($opacity, 2, '.', ''), '0'), '.');
        }

        throw new ValidationException('Invalid setting');
    }

    /**
     * @throws ValidationException
     */
    private static function normalizeBackgroundUrl(string $value): string {
        if ($value === '') {
            return '';
        }
        if (strlen($value) > 2048 || preg_match('/[\x00-\x20\x7F]/', $value) === 1) {
            throw new ValidationException('Invalid setting');
        }

        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
            if (str_contains($value, "'") || str_contains($value, '"') || str_contains($value, '\\')) {
                throw new ValidationException('Invalid setting');
            }
            return $value;
        }

        $parts = parse_url($value);
        if (
            filter_var($value, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            throw new ValidationException('Invalid setting');
        }

        return $value;
    }
}
