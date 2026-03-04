<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ImmichWidget extends AbstractWidget {

    public function getId(): string { return 'immich'; }
    public function getLabel(): string { return 'Immich'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['users', 'photos', 'videos', 'storage']; }

    public function getFieldLabels(): array {
        return ['users' => 'Users', 'photos' => 'Photos', 'videos' => 'Videos', 'storage' => 'Storage'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['x-api-key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/server/statistics', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $stats = $data['usageByUser'] ?? [];
        $users = is_array($stats) ? count($stats) : 0;
        $photos = (int)($data['photos'] ?? 0);
        $videos = (int)($data['videos'] ?? 0);
        $usage = (int)($data['usage'] ?? 0);
        $gb = round($usage / 1073741824, 1);
        return [
            'users' => (string)$users,
            'photos' => (string)$photos,
            'videos' => (string)$videos,
            'storage' => $gb . ' GB',
        ];
    }
}
