<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PhotoPrismWidget extends AbstractWidget {

    public function getId(): string { return 'photoprism'; }
    public function getLabel(): string { return 'PhotoPrism'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'placeholder' => 'admin'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['photos', 'videos', 'albums', 'places']; }

    public function getFieldLabels(): array {
        return ['photos' => 'Photos', 'videos' => 'Videos', 'albums' => 'Albums', 'places' => 'Places'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/v1/session',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['username' => $config['username'] ?? 'admin', 'password' => $config['password'] ?? '']),
                '_photoprism_login' => true,
            ],
            ['url' => $base . '/api/v1/stats', 'headers' => [], '_photoprism_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats = $responses[1] ?? [];
        return [
            'photos' => (string)($stats['photos'] ?? 0),
            'videos' => (string)($stats['videos'] ?? 0),
            'albums' => (string)($stats['albums'] ?? 0),
            'places' => (string)($stats['places'] ?? 0),
        ];
    }
}
