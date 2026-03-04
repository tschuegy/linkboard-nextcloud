<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class OpenWrtWidget extends AbstractWidget {

    public function getId(): string { return 'openwrt'; }
    public function getLabel(): string { return 'OpenWRT'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => 'root'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['cpuLoad', 'memUsed', 'uptime', 'leases']; }

    public function getFieldLabels(): array {
        return ['cpuLoad' => 'CPU', 'memUsed' => 'Memory', 'uptime' => 'Uptime', 'leases' => 'Leases'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Content-Type: application/json'];
        return [
            [
                'url' => $base . '/cgi-bin/luci/rpc/auth',
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode(['id' => 1, 'method' => 'login', 'params' => [$config['username'] ?? 'root', $config['password'] ?? '']]),
                '_openwrt_login' => true,
            ],
            [
                'url' => $base . '/cgi-bin/luci/rpc/sys',
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode(['id' => 1, 'method' => 'exec', 'params' => ['cat /proc/loadavg']]),
                '_openwrt_needs_token' => true,
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        return [
            'cpuLoad' => '0%',
            'memUsed' => '0%',
            'uptime' => '0s',
            'leases' => '0',
        ];
    }
}
