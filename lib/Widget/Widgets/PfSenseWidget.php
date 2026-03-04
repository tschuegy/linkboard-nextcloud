<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PfSenseWidget extends AbstractWidget {

    public function getId(): string { return 'pfsense'; }
    public function getLabel(): string { return 'pfSense'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'API Client ID', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'API Client Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['cpuUsage', 'memUsage', 'wanStatus']; }

    public function getFieldLabels(): array {
        return ['cpuUsage' => 'CPU', 'memUsage' => 'Memory', 'wanStatus' => 'WAN'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [
            'Authorization: ' . ($config['username'] ?? '') . ' ' . ($config['password'] ?? ''),
        ];
        return [
            ['url' => $base . '/api/v1/status/system', 'headers' => $headers],
            ['url' => $base . '/api/v1/status/interface', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $system = $responses[0]['data'] ?? [];
        $cpuUsage = round(100 - (float)($system['cpu_idle'] ?? 100));
        $memUsage = (int)($system['mem_usage'] ?? 0);
        $ifaces = $responses[1]['data'] ?? [];
        $wanStatus = 'down';
        if (is_array($ifaces)) {
            foreach ($ifaces as $iface) {
                if (($iface['if'] ?? '') === 'wan' || ($iface['descr'] ?? '') === 'WAN') {
                    $wanStatus = ($iface['status'] ?? 'down') === 'up' ? 'up' : 'down';
                    break;
                }
            }
        }
        return [
            'cpuUsage' => $cpuUsage . '%',
            'memUsage' => $memUsage . '%',
            'wanStatus' => $wanStatus,
        ];
    }
}
