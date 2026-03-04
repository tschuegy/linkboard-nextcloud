<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class JellyfinWidget extends AbstractWidget {

    public function getId(): string { return 'jellyfin'; }
    public function getLabel(): string { return 'Jellyfin'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['movies', 'series', 'episodes', 'songs']; }

    public function getFieldLabels(): array {
        return ['movies' => 'Movies', 'series' => 'Series', 'episodes' => 'Episodes', 'songs' => 'Songs'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        return [[
            'url' => rtrim($baseUrl, '/') . '/Items/Counts',
            'headers' => ['X-Emby-Token: ' . ($config['api_key'] ?? '')],
        ]];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'movies' => (string)($data['MovieCount'] ?? 0),
            'series' => (string)($data['SeriesCount'] ?? 0),
            'episodes' => (string)($data['EpisodeCount'] ?? 0),
            'songs' => (string)($data['SongCount'] ?? 0),
        ];
    }
}
