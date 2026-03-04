<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class HomeBoxWidget extends AbstractWidget {

    public function getId(): string { return 'homebox'; }
    public function getLabel(): string { return 'HomeBox'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['items', 'locations', 'labels', 'totalValue']; }

    public function getFieldLabels(): array {
        return ['items' => 'Items', 'locations' => 'Locations', 'labels' => 'Labels', 'totalValue' => 'Value'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/v1/users/login',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_homebox_login' => true,
            ],
            ['url' => $base . '/api/v1/groups/statistics', 'headers' => [], '_homebox_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats = $responses[1] ?? [];
        return [
            'items' => (string)($stats['totalItems'] ?? 0),
            'locations' => (string)($stats['totalLocations'] ?? 0),
            'labels' => (string)($stats['totalLabels'] ?? 0),
            'totalValue' => '$' . number_format((float)($stats['totalValue'] ?? 0), 2),
        ];
    }
}
