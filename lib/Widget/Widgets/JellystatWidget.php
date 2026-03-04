<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class JellystatWidget extends AbstractWidget {

    public function getId(): string { return 'jellystat'; }
    public function getLabel(): string { return 'Jellystat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['activeStreams', 'totalUsers', 'totalMedia']; }

    public function getFieldLabels(): array {
        return ['activeStreams' => 'Streams', 'totalUsers' => 'Users', 'totalMedia' => 'Media'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['x-api-token: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/stats', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'activeStreams' => (string)($data['active_streams'] ?? 0),
            'totalUsers' => (string)($data['total_users'] ?? 0),
            'totalMedia' => (string)($data['total_media'] ?? 0),
        ];
    }
}
