<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class UnmanicWidget extends AbstractWidget {

    public function getId(): string { return 'unmanic'; }
    public function getLabel(): string { return 'Unmanic'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['pending', 'inProgress', 'completed', 'totalFiles']; }

    public function getFieldLabels(): array {
        return ['pending' => 'Pending', 'inProgress' => 'In Progress', 'completed' => 'Completed', 'totalFiles' => 'Total'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/unmanic/api/v2/pending/list', 'method' => 'POST', 'headers' => ['Content-Type: application/json'], 'body' => json_encode(['start' => 0, 'length' => 0])],
            ['url' => $base . '/unmanic/api/v2/history/list', 'method' => 'POST', 'headers' => ['Content-Type: application/json'], 'body' => json_encode(['start' => 0, 'length' => 0])],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $pending = (int)($responses[0]['recordsTotal'] ?? 0);
        $completed = (int)($responses[1]['recordsTotal'] ?? 0);
        return [
            'pending' => (string)$pending,
            'inProgress' => '0',
            'completed' => (string)$completed,
            'totalFiles' => (string)($pending + $completed),
        ];
    }
}
