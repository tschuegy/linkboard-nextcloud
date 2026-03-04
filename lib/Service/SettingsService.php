<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

use OCA\LinkBoard\Db\SettingMapper;

class SettingsService {

    public function __construct(
        private SettingMapper $settingMapper,
    ) {
    }

    /**
     * Get all settings with defaults applied
     */
    public function getAll(string $userId): array {
        return $this->settingMapper->getSettingsMap($userId);
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
    public function set(string $key, string $value, string $userId): void {
        $this->settingMapper->setSetting($key, $value, $userId);
    }

    /**
     * Update multiple settings at once
     * @param array<string, string> $settings
     */
    public function setMultiple(array $settings, string $userId): void {
        foreach ($settings as $key => $value) {
            $this->settingMapper->setSetting($key, (string)$value, $userId);
        }
    }
}
