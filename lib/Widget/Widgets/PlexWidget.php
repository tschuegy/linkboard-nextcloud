<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PlexWidget extends AbstractWidget {

    public function getId(): string { return 'plex'; }
    public function getLabel(): string { return 'Plex'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'token', 'label' => 'X-Plex-Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['streams', 'movies', 'shows']; }

    public function getFieldLabels(): array {
        return ['streams' => 'Streams', 'movies' => 'Movies', 'shows' => 'Shows'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $token = $config['token'] ?? '';
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/status/sessions',
                'headers' => ['X-Plex-Token: ' . $token, 'Accept: application/json'],
            ],
            [
                'url' => $base . '/library/sections',
                'headers' => ['X-Plex-Token: ' . $token, 'Accept: application/json'],
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $sessions = $responses[0]['MediaContainer'] ?? [];
        $libraries = $responses[1]['MediaContainer'] ?? [];

        $streams = (int)($sessions['size'] ?? 0);
        $movies = 0; $shows = 0;

        $dirs = $libraries['Directory'] ?? [];
        if (is_array($dirs)) {
            foreach ($dirs as $dir) {
                $type = $dir['type'] ?? '';
                if ($type === 'movie') $movies++;
                elseif ($type === 'show') $shows++;
            }
        }

        return [
            'streams' => (string)$streams,
            'movies' => (string)$movies,
            'shows' => (string)$shows,
        ];
    }
}
