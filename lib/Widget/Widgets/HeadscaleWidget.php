<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class HeadscaleWidget extends AbstractWidget {

    public function getId(): string { return 'headscale'; }
    public function getLabel(): string { return 'Headscale'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['devices', 'online', 'users']; }

    public function getFieldLabels(): array {
        return ['devices' => 'Devices', 'online' => 'Online', 'users' => 'Users'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/machine', 'headers' => $headers],
            ['url' => $base . '/api/v1/user', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $machines = $responses[0]['machines'] ?? [];
        if (!is_array($machines)) $machines = [];
        $online = 0;
        foreach ($machines as $m) {
            if ($m['online'] ?? false) $online++;
        }
        $users = $responses[1]['users'] ?? [];
        return [
            'devices' => (string)count($machines),
            'online' => (string)$online,
            'users' => (string)(is_array($users) ? count($users) : 0),
        ];
    }
}
