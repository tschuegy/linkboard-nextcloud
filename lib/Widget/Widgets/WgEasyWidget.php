<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class WgEasyWidget extends AbstractWidget {

    public function getId(): string { return 'wgeasy'; }
    public function getLabel(): string { return 'WG-Easy'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['connected', 'total', 'enabled']; }

    public function getFieldLabels(): array {
        return ['connected' => 'Connected', 'total' => 'Total', 'enabled' => 'Enabled'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/session',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['password' => $config['password'] ?? '']),
                '_wgeasy_login' => true,
            ],
            ['url' => $base . '/api/wireguard/client', 'headers' => [], '_wgeasy_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $clients = $responses[1] ?? [];
        if (!is_array($clients)) $clients = [];
        $connected = 0; $enabled = 0;
        foreach ($clients as $c) {
            if ($c['enabled'] ?? false) $enabled++;
            $endpoint = $c['latestHandshakeAt'] ?? null;
            if ($endpoint) $connected++;
        }
        return [
            'connected' => (string)$connected,
            'total' => (string)count($clients),
            'enabled' => (string)$enabled,
        ];
    }
}
