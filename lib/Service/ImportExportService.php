<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Service;

use DateTime;
use OCA\LinkBoard\Db\Category;
use OCA\LinkBoard\Db\CategoryMapper;
use OCA\LinkBoard\Db\Service;
use OCA\LinkBoard\Db\ServiceMapper;
use OCA\LinkBoard\Db\SettingMapper;
use OCA\LinkBoard\Db\Setting;

class ImportExportService {

    public function __construct(
        private CategoryMapper $categoryMapper,
        private ServiceMapper $serviceMapper,
        private SettingMapper $settingMapper,
        private CategoryService $categoryService,
        private ServiceService $serviceService,
        private SettingsService $settingsService,
    ) {
    }

    /**
     * Export entire dashboard as structured array
     */
    public function export(string $userId): array {
        $settings = $this->settingsService->getAll($userId);
        $categories = $this->categoryMapper->findAllByUser($userId);
        $allServices = $this->serviceMapper->findAllByUser($userId);

        $servicesByCategory = [];
        foreach ($allServices as $service) {
            $catId = $service->getCategoryId();
            $servicesByCategory[$catId][] = $service->jsonSerialize();
        }

        $exportCategories = [];
        foreach ($categories as $category) {
            $catData = $category->jsonSerialize();
            unset($catData['userId']);
            $catData['services'] = array_map(function ($svc) {
                unset($svc['userId'], $svc['categoryId']);
                return $svc;
            }, $servicesByCategory[$category->getId()] ?? []);
            $exportCategories[] = $catData;
        }

        return [
            'version' => '1.0',
            'exportedAt' => (new DateTime())->format('c'),
            'app' => 'linkboard',
            'settings' => $settings,
            'categories' => $exportCategories,
        ];
    }

    /**
     * Import dashboard from structured array.
     * @param string $mode 'replace' = delete existing, 'merge' = add to existing
     */
    public function import(string $userId, array $data, string $mode = 'replace'): array {
        $stats = ['categories' => 0, 'services' => 0, 'settings' => 0];

        if ($mode === 'replace') {
            // Delete all existing data
            $this->serviceMapper->deleteAllByUser($userId);
            $categories = $this->categoryMapper->findAllByUser($userId);
            foreach ($categories as $cat) {
                $this->categoryMapper->delete($cat);
            }
        }

        // Import settings
        if (isset($data['settings']) && is_array($data['settings'])) {
            foreach ($data['settings'] as $key => $value) {
                if (is_string($key) && !empty($key)) {
                    $this->settingsService->set($key, is_string($value) ? $value : json_encode($value), $userId);
                    $stats['settings']++;
                }
            }
        }

        // Import categories and services (two-pass for parentId)
        if (isset($data['categories']) && is_array($data['categories'])) {
            $now = (new DateTime())->format('Y-m-d H:i:s');
            $oldIdToNewId = [];

            // Pass 1: Create all categories without parentId
            foreach ($data['categories'] as $catData) {
                if (empty($catData['name'])) continue;
                $oldId = $catData['id'] ?? null;

                $category = $this->categoryService->create(
                    $userId,
                    $catData['name'],
                    $catData['icon'] ?? null,
                    $catData['tab'] ?? null,
                    isset($catData['columns']) ? (int)$catData['columns'] : null,
                    (bool)($catData['collapsed'] ?? false),
                );
                $stats['categories']++;

                if ($oldId !== null) {
                    $oldIdToNewId[$oldId] = $category->getId();
                }

                // Import services for this category
                $services = $catData['services'] ?? [];
                foreach ($services as $svcData) {
                    if (empty($svcData['name'])) continue;

                    $this->serviceService->create(
                        $userId,
                        $category->getId(),
                        $svcData['name'],
                        $svcData['description'] ?? null,
                        $svcData['href'] ?? null,
                        $svcData['icon'] ?? null,
                        $svcData['iconColor'] ?? null,
                        $svcData['target'] ?? '_blank',
                        $svcData['pingUrl'] ?? null,
                        (bool)($svcData['pingEnabled'] ?? false),
                        $svcData['widgetType'] ?? null,
                        $svcData['widgetConfig'] ?? null,
                        $svcData['notificationOverrides'] ?? null,
                    );
                    $stats['services']++;
                }
            }

            // Pass 2: Set parentId for child categories
            foreach ($data['categories'] as $catData) {
                $oldParentId = $catData['parentId'] ?? null;
                $oldId = $catData['id'] ?? null;
                if ($oldParentId !== null && $oldId !== null
                    && isset($oldIdToNewId[$oldId]) && isset($oldIdToNewId[$oldParentId])) {
                    try {
                        $this->categoryService->moveCategory(
                            $oldIdToNewId[$oldId],
                            $oldIdToNewId[$oldParentId],
                            $userId
                        );
                    } catch (\Exception $e) {
                        // Skip if nesting fails
                    }
                }
            }
        }

        // Handle Gethomepage format
        if (!isset($data['categories']) && !isset($data['version'])) {
            $stats = $this->importGethomepage($userId, $data, $mode);
        }

        return $stats;
    }

    /**
     * Import from Gethomepage services.yaml format
     * Format: array of groups, each group key is category name, value is array of services
     */
    private function importGethomepage(string $userId, array $data, string $mode): array {
        $stats = ['categories' => 0, 'services' => 0, 'settings' => 0];

        foreach ($data as $groupOrKey => $value) {
            if (is_array($value) && !isset($value['name'])) {
                // This is a category group
                // Gethomepage format: [{"CategoryName": [{"ServiceName": [{...}]}]}]
                if (is_int($groupOrKey) && is_array($value)) {
                    // Top-level array item: {"CategoryName": [...services]}
                    foreach ($value as $catName => $services) {
                        if (!is_string($catName) || !is_array($services)) continue;

                        $category = $this->categoryService->create($userId, $catName);
                        $stats['categories']++;

                        foreach ($services as $svcEntry) {
                            if (!is_array($svcEntry)) continue;
                            foreach ($svcEntry as $svcName => $svcProps) {
                                if (!is_string($svcName)) continue;
                                $props = is_array($svcProps) ? ($svcProps[0] ?? $svcProps) : [];

                                $this->serviceService->create(
                                    $userId,
                                    $category->getId(),
                                    $svcName,
                                    $props['description'] ?? null,
                                    $props['href'] ?? $props['url'] ?? null,
                                    $props['icon'] ?? null,
                                );
                                $stats['services']++;
                            }
                        }
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Generate simple YAML from export data
     */
    public function toYaml(array $data): string {
        $lines = [];
        $lines[] = "# LinkBoard Dashboard Export";
        $lines[] = "# Exported: " . ($data['exportedAt'] ?? date('c'));
        $lines[] = "";

        // Settings
        if (!empty($data['settings'])) {
            $lines[] = "settings:";
            foreach ($data['settings'] as $key => $value) {
                $lines[] = "  {$key}: " . $this->yamlValue($value);
            }
            $lines[] = "";
        }

        // Categories
        if (!empty($data['categories'])) {
            $lines[] = "categories:";
            foreach ($data['categories'] as $cat) {
                $lines[] = "  - name: " . $this->yamlValue($cat['name']);
                if (!empty($cat['icon'])) {
                    $lines[] = "    icon: " . $this->yamlValue($cat['icon']);
                }
                if (!empty($cat['tab'])) {
                    $lines[] = "    tab: " . $this->yamlValue($cat['tab']);
                }
                if (!empty($cat['columns'])) {
                    $lines[] = "    columns: " . $cat['columns'];
                }
                if (!empty($cat['collapsed'])) {
                    $lines[] = "    collapsed: true";
                }
                if (!empty($cat['parentId'])) {
                    $lines[] = "    parentId: " . $cat['parentId'];
                }

                if (!empty($cat['services'])) {
                    $lines[] = "    services:";
                    foreach ($cat['services'] as $svc) {
                        $lines[] = "      - name: " . $this->yamlValue($svc['name']);
                        if (!empty($svc['description'])) {
                            $lines[] = "        description: " . $this->yamlValue($svc['description']);
                        }
                        if (!empty($svc['href'])) {
                            $lines[] = "        href: " . $this->yamlValue($svc['href']);
                        }
                        if (!empty($svc['icon'])) {
                            $lines[] = "        icon: " . $this->yamlValue($svc['icon']);
                        }
                        if (!empty($svc['pingUrl'])) {
                            $lines[] = "        pingUrl: " . $this->yamlValue($svc['pingUrl']);
                        }
                        if (!empty($svc['pingEnabled'])) {
                            $lines[] = "        pingEnabled: true";
                        }
                        if (!empty($svc['target']) && $svc['target'] !== '_blank') {
                            $lines[] = "        target: " . $this->yamlValue($svc['target']);
                        }
                    }
                }
                $lines[] = "";
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function yamlValue(mixed $value): string {
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_int($value) || is_float($value)) return (string)$value;
        if (is_null($value)) return '~';
        $str = (string)$value;
        // Quote if contains special chars
        if (preg_match('/[:#\[\]{}&*!|>\'"%@`]/', $str) || $str === '' || $str !== trim($str)) {
            return '"' . addcslashes($str, '"\\') . '"';
        }
        return $str;
    }
}
