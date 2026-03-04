<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TrueNasWidget extends AbstractWidget {

    public function getId(): string { return 'truenas'; }
    public function getLabel(): string { return 'TrueNAS'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => '1-abc...'],
        ];
    }

    public function getAllowedFields(): array { return ['load', 'uptime', 'alerts']; }

    public function getFieldLabels(): array {
        return ['load' => 'Load', 'uptime' => 'Uptime', 'alerts' => 'Alerts'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => rtrim($baseUrl, '/') . '/api/v2.0/system/info', 'headers' => $headers],
            ['url' => rtrim($baseUrl, '/') . '/api/v2.0/alert/list', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $info = $responses[0] ?? [];
        $alerts = $responses[1] ?? [];
        if (!is_array($alerts)) $alerts = [];

        $loadavg = $info['loadavg'] ?? [0, 0, 0];
        $load = is_array($loadavg) ? round($loadavg[0] ?? 0, 2) : '0';

        $boottime = $info['boottime'] ?? null;
        $uptime = 'N/A';
        if ($boottime) {
            $diff = time() - (int)strtotime($boottime);
            $days = (int)floor($diff / 86400);
            $uptime = $days . 'd';
        } elseif (isset($info['uptime_seconds'])) {
            $days = (int)floor((int)$info['uptime_seconds'] / 86400);
            $uptime = $days . 'd';
        }

        $activeAlerts = array_filter($alerts, fn($a) => !($a['dismissed'] ?? false));

        return [
            'load' => (string)$load,
            'uptime' => $uptime,
            'alerts' => (string)count($activeAlerts),
        ];
    }
}
