<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TautulliWidget extends AbstractWidget {

    public function getId(): string { return 'tautulli'; }
    public function getLabel(): string { return 'Tautulli'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['streams', 'albums', 'movies', 'series']; }

    public function getFieldLabels(): array {
        return ['streams' => 'Streams', 'albums' => 'Albums', 'movies' => 'Movies', 'series' => 'Series'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $key = $config['api_key'] ?? '';
        return [
            ['url' => $base . '/api/v2?apikey=' . $key . '&cmd=get_activity'],
            ['url' => $base . '/api/v2?apikey=' . $key . '&cmd=get_libraries'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $activity = $responses[0]['response']['data'] ?? [];
        $streams = (int)($activity['stream_count'] ?? 0);

        $libs = $responses[1]['response']['data'] ?? [];
        $albums = 0; $movies = 0; $series = 0;
        if (is_array($libs)) {
            foreach ($libs as $lib) {
                $type = $lib['section_type'] ?? '';
                $count = (int)($lib['count'] ?? 0);
                if ($type === 'movie') $movies += $count;
                elseif ($type === 'show') $series += $count;
                elseif ($type === 'artist') $albums += $count;
            }
        }
        return [
            'streams' => (string)$streams,
            'albums' => (string)$albums,
            'movies' => (string)$movies,
            'series' => (string)$series,
        ];
    }
}
