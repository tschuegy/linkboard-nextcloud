<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class OmadaWidget extends AbstractWidget {

    public function getId(): string { return 'omada'; }
    public function getLabel(): string { return 'Omada'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'site', 'label' => 'Site Name', 'type' => 'text', 'required' => false, 'placeholder' => 'Default'],
        ];
    }

    public function getAllowedFields(): array { return ['connectedAps', 'activeClients', 'alerts']; }

    public function getFieldLabels(): array {
        return ['connectedAps' => 'APs', 'activeClients' => 'Clients', 'alerts' => 'Alerts'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/v2/login',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_omada_login' => true,
            ],
            ['url' => $base . '/api/v2/sites/default/dashboard', 'headers' => [], '_omada_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[1]['result'] ?? $responses[1] ?? [];
        return [
            'connectedAps' => (string)($data['connectedAps'] ?? 0),
            'activeClients' => (string)($data['activeClients'] ?? 0),
            'alerts' => (string)($data['alerts'] ?? 0),
        ];
    }
}
