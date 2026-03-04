<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class HomebridgeWidget extends AbstractWidget {

    public function getId(): string { return 'homebridge'; }
    public function getLabel(): string { return 'Homebridge'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => 'admin'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['accessories', 'childBridges', 'status']; }

    public function getFieldLabels(): array {
        return ['accessories' => 'Accessories', 'childBridges' => 'Bridges', 'status' => 'Status'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/auth/login',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_homebridge_login' => true,
            ],
            ['url' => $base . '/api/accessories', 'headers' => [], '_homebridge_needs_token' => true],
            ['url' => $base . '/api/status/homebridge', 'headers' => [], '_homebridge_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $accessories = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $status = $responses[2] ?? [];
        $childBridges = is_array($status['childBridges'] ?? null) ? count($status['childBridges']) : 0;
        $running = ($status['status'] ?? '') === 'up' ? 'running' : 'stopped';
        return [
            'accessories' => (string)$accessories,
            'childBridges' => (string)$childBridges,
            'status' => $running,
        ];
    }
}
