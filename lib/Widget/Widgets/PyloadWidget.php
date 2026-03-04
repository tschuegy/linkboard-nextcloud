<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PyloadWidget extends AbstractWidget {

    public function getId(): string { return 'pyload'; }
    public function getLabel(): string { return 'pyLoad'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['speed', 'active', 'queue', 'total']; }

    public function getFieldLabels(): array {
        return ['speed' => 'Speed', 'active' => 'Active', 'queue' => 'Queue', 'total' => 'Total'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/login',
                'method' => 'POST',
                'headers' => ['Content-Type: application/x-www-form-urlencoded'],
                'body' => http_build_query(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_pyload_login' => true,
            ],
            ['url' => $base . '/api/statusServer', 'headers' => [], '_pyload_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[1] ?? [];
        $speed = round((float)($data['speed'] ?? 0) / 1024, 1);
        return [
            'speed' => $speed . ' KB/s',
            'active' => (string)($data['active'] ?? 0),
            'queue' => (string)($data['queue'] ?? 0),
            'total' => (string)($data['total'] ?? 0),
        ];
    }
}
