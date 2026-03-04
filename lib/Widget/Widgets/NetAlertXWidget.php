<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class NetAlertXWidget extends AbstractWidget {

    public function getId(): string { return 'netalertx'; }
    public function getLabel(): string { return 'NetAlertX'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['online', 'offline', 'newDevices', 'alerts']; }

    public function getFieldLabels(): array {
        return ['online' => 'Online', 'offline' => 'Offline', 'newDevices' => 'New', 'alerts' => 'Alerts'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/table_devices.json'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $devices = $responses[0] ?? [];
        if (!is_array($devices)) $devices = [];
        $online = 0; $offline = 0; $new = 0; $alerts = 0;
        foreach ($devices as $d) {
            $status = $d['dev_PresentLastScan'] ?? 0;
            if ($status == 1) $online++;
            else $offline++;
            if ($d['dev_NewDevice'] ?? false) $new++;
            if ($d['dev_AlertEvents'] ?? 0) $alerts++;
        }
        return [
            'online' => (string)$online,
            'offline' => (string)$offline,
            'newDevices' => (string)$new,
            'alerts' => (string)$alerts,
        ];
    }
}
