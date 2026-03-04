<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class AudiobookshelfWidget extends AbstractWidget {

    public function getId(): string { return 'audiobookshelf'; }
    public function getLabel(): string { return 'Audiobookshelf'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['books', 'podcasts', 'podcastEpisodes', 'totalDuration']; }

    public function getFieldLabels(): array {
        return ['books' => 'Books', 'podcasts' => 'Podcasts', 'podcastEpisodes' => 'Episodes', 'totalDuration' => 'Duration'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/libraries', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $libs = $responses[0]['libraries'] ?? [];
        $books = 0; $podcasts = 0; $episodes = 0; $duration = 0;
        if (is_array($libs)) {
            foreach ($libs as $lib) {
                $stats = $lib['stats'] ?? [];
                $type = $lib['mediaType'] ?? '';
                if ($type === 'book') {
                    $books += (int)($stats['totalItems'] ?? 0);
                    $duration += (float)($stats['totalDuration'] ?? 0);
                } elseif ($type === 'podcast') {
                    $podcasts += (int)($stats['totalItems'] ?? 0);
                    $episodes += (int)($stats['numAudioTracks'] ?? 0);
                }
            }
        }
        $hours = round($duration / 3600);
        return [
            'books' => (string)$books,
            'podcasts' => (string)$podcasts,
            'podcastEpisodes' => (string)$episodes,
            'totalDuration' => $hours . 'h',
        ];
    }
}
