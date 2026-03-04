<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class StashWidget extends AbstractWidget {

    public function getId(): string { return 'stash'; }
    public function getLabel(): string { return 'Stash'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['scenes', 'images', 'studios', 'performers']; }

    public function getFieldLabels(): array {
        return ['scenes' => 'Scenes', 'images' => 'Images', 'studios' => 'Studios', 'performers' => 'Performers'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['ApiKey: ' . ($config['api_key'] ?? ''), 'Content-Type: application/json'];
        $query = '{"query":"{ stats { scene_count image_count studio_count performer_count } }"}';
        return [
            ['url' => $base . '/graphql', 'method' => 'POST', 'headers' => $headers, 'body' => $query],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats = $responses[0]['data']['stats'] ?? [];
        return [
            'scenes' => (string)($stats['scene_count'] ?? 0),
            'images' => (string)($stats['image_count'] ?? 0),
            'studios' => (string)($stats['studio_count'] ?? 0),
            'performers' => (string)($stats['performer_count'] ?? 0),
        ];
    }
}
