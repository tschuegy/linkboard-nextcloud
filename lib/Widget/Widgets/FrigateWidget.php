<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class FrigateWidget extends AbstractWidget {

    public function getId(): string { return 'frigate'; }
    public function getLabel(): string { return 'Frigate'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['cameras', 'uptime', 'version']; }

    public function getFieldLabels(): array {
        return ['cameras' => 'Cameras', 'uptime' => 'Uptime', 'version' => 'Version'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/stats'],
            ['url' => $base . '/api/version'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats = $responses[0] ?? [];
        $cameras = is_array($stats['cameras'] ?? null) ? count($stats['cameras']) : 0;
        $uptime = (int)($stats['uptime'] ?? 0);
        $hours = round($uptime / 3600);
        $version = $responses[1]['version'] ?? (is_string($responses[1] ?? null) ? $responses[1] : '—');
        return [
            'cameras' => (string)$cameras,
            'uptime' => $hours . 'h',
            'version' => (string)$version,
        ];
    }
}
