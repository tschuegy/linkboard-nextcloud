<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class OpenMediaVaultWidget extends AbstractWidget {

    public function getId(): string { return 'openmediavault'; }
    public function getLabel(): string { return 'OpenMediaVault'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => 'admin'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['cpuUsage', 'memUsage', 'filesystems']; }

    public function getFieldLabels(): array {
        return ['cpuUsage' => 'CPU', 'memUsage' => 'Memory', 'filesystems' => 'Filesystems'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Content-Type: application/json'];
        return [
            [
                'url' => $base . '/rpc.php',
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode(['service' => 'Session', 'method' => 'login', 'params' => ['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']]),
                '_session_login' => true,
            ],
            [
                'url' => $base . '/rpc.php',
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode(['service' => 'System', 'method' => 'getInformation', 'params' => []]),
                '_session_needs_cookie' => true,
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $info = $responses[1]['response'] ?? $responses[1] ?? [];
        return [
            'cpuUsage' => round((float)($info['cpuUsage'] ?? 0)) . '%',
            'memUsage' => round((float)($info['memUsage'] ?? 0)) . '%',
            'filesystems' => (string)($info['configDirty'] ?? 0),
        ];
    }
}
