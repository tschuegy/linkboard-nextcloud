<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class BazarrWidget extends AbstractWidget {

    public function getId(): string { return 'bazarr'; }
    public function getLabel(): string { return 'Bazarr'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['missingMovies', 'missingEpisodes']; }

    public function getFieldLabels(): array {
        return ['missingMovies' => 'Missing Movies', 'missingEpisodes' => 'Missing Episodes'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $key = $config['api_key'] ?? '';
        return [
            ['url' => $base . '/api/movies/wanted?apikey=' . $key],
            ['url' => $base . '/api/episodes/wanted?apikey=' . $key],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $movies = $responses[0]['total'] ?? 0;
        $episodes = $responses[1]['total'] ?? 0;
        return [
            'missingMovies' => (string)$movies,
            'missingEpisodes' => (string)$episodes,
        ];
    }
}
