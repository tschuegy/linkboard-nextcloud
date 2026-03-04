<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ProwlarrWidget extends AbstractWidget {

    public function getId(): string { return 'prowlarr'; }
    public function getLabel(): string { return 'Prowlarr'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['indexers', 'grabs', 'queries', 'failedGrabs']; }

    public function getFieldLabels(): array {
        return ['indexers' => 'Indexers', 'grabs' => 'Grabs', 'queries' => 'Queries', 'failedGrabs' => 'Failed'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Api-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/indexer', 'headers' => $headers],
            ['url' => $base . '/api/v1/indexerstats', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $indexers = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $stats = $responses[1]['indexers'] ?? [];
        $grabs = 0; $queries = 0; $failedGrabs = 0;
        if (is_array($stats)) {
            foreach ($stats as $s) {
                $grabs += (int)($s['numberOfGrabs'] ?? 0);
                $queries += (int)($s['numberOfQueries'] ?? 0);
                $failedGrabs += (int)($s['numberOfFailedGrabs'] ?? 0);
            }
        }
        return [
            'indexers' => (string)$indexers,
            'grabs' => (string)$grabs,
            'queries' => (string)$queries,
            'failedGrabs' => (string)$failedGrabs,
        ];
    }
}
