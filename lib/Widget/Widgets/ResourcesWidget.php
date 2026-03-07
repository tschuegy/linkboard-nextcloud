<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ResourcesWidget extends AbstractWidget {

    public function getId(): string { return 'resources'; }
    public function getLabel(): string { return 'System Resources'; }

    public function isLocal(): bool { return true; }

    public function getConfigFields(): array {
        return [
            ['key' => 'diskPaths', 'label' => 'Disk Paths', 'type' => 'text', 'required' => false, 'placeholder' => '/, /mnt/data'],
            ['key' => 'tempUnit', 'label' => 'Temperature Unit', 'type' => 'text', 'required' => false, 'placeholder' => 'C or F'],
        ];
    }

    public function getAllowedFields(): array { return []; }
    public function getFieldLabels(): array { return []; }

    public function getFieldLabelsForConfig(array $config): array {
        $labels = [];
        foreach ($this->resolveMappings($config) as $mapping) {
            if (!empty($mapping['field']) && !empty($mapping['label'])) {
                $labels[$mapping['field']] = $mapping['label'];
            }
        }
        return $labels;
    }

    public function buildRequests(string $baseUrl, array $config): array {
        return [];
    }

    public function mapResponse(array $responses, array $config): array {
        return $responses[0] ?? [];
    }

    private function getDefaultMappings(): array {
        return [
            ['field' => 'cpu.percent', 'label' => 'CPU', 'suffix' => '%'],
            ['field' => 'memory.percent', 'label' => 'Memory', 'suffix' => '%'],
            ['field' => 'disks.0.percent', 'label' => 'Disk', 'suffix' => '%'],
            ['field' => 'uptime', 'label' => 'Uptime'],
        ];
    }

    private function resolveMappings(array $config): array {
        $mappings = $config['mappings'] ?? [];
        if (is_array($mappings) && !empty($mappings)) {
            return $mappings;
        }
        return $this->getDefaultMappings();
    }

    private function resolveFieldPath(mixed $data, string $path): mixed {
        $segments = explode('.', $path);
        $current = $data;
        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } elseif (is_array($current) && ctype_digit($segment) && array_key_exists((int)$segment, $current)) {
                $current = $current[(int)$segment];
            } else {
                return '—';
            }
        }
        return $current;
    }
}
