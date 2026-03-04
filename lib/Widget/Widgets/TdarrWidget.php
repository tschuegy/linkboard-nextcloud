<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TdarrWidget extends AbstractWidget {

    public function getId(): string { return 'tdarr'; }
    public function getLabel(): string { return 'Tdarr'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['queue', 'processed', 'healthChecks', 'transcodes']; }

    public function getFieldLabels(): array {
        return ['queue' => 'Queue', 'processed' => 'Processed', 'healthChecks' => 'Health', 'transcodes' => 'Transcodes'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/v2/cruddb',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['data' => ['collection' => 'StatisticsJSONDB', 'mode' => 'getById', 'docID' => 'statistics']]),
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats = $responses[0] ?? [];
        return [
            'queue' => (string)($stats['table1Count'] ?? 0),
            'processed' => (string)($stats['table2Count'] ?? 0),
            'healthChecks' => (string)($stats['healthCheckCount'] ?? 0),
            'transcodes' => (string)($stats['transcodeCount'] ?? 0),
        ];
    }
}
