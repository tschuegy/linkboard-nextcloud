<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class EsphomeWidget extends AbstractWidget {

    public function getId(): string { return 'esphome'; }
    public function getLabel(): string { return 'ESPHome'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['online', 'offline', 'total']; }

    public function getFieldLabels(): array {
        return ['online' => 'Online', 'offline' => 'Offline', 'total' => 'Total'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/devices'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $devices = $responses[0] ?? [];
        if (!is_array($devices)) $devices = [];
        $online = 0; $offline = 0;
        foreach ($devices as $d) {
            if (is_array($d)) {
                if ($d['status'] ?? '' === 'ONLINE') $online++;
                else $offline++;
            }
        }
        return [
            'online' => (string)$online,
            'offline' => (string)$offline,
            'total' => (string)count($devices),
        ];
    }
}
