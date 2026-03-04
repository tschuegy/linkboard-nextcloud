<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TubeArchivistWidget extends AbstractWidget {

    public function getId(): string { return 'tubearchivist'; }
    public function getLabel(): string { return 'TubeArchivist'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['downloads', 'videos', 'channels', 'playlists']; }

    public function getFieldLabels(): array {
        return ['downloads' => 'Downloads', 'videos' => 'Videos', 'channels' => 'Channels', 'playlists' => 'Playlists'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Token ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/stats/', 'headers' => $headers],
            ['url' => $base . '/api/download/?limit=0', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats = $responses[0] ?? [];
        $dl = $responses[1]['paginate']['total_hits'] ?? 0;
        return [
            'downloads' => (string)$dl,
            'videos' => (string)($stats['videos'] ?? 0),
            'channels' => (string)($stats['channels'] ?? 0),
            'playlists' => (string)($stats['playlists'] ?? 0),
        ];
    }
}
