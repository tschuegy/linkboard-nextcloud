<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class GatusWidget extends AbstractWidget {

    public function getId(): string { return 'gatus'; }
    public function getLabel(): string { return 'Gatus'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['up', 'down', 'uptime']; }

    public function getFieldLabels(): array {
        return ['up' => 'Up', 'down' => 'Down', 'uptime' => 'Uptime'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/v1/endpoints/statuses'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $endpoints = $responses[0] ?? [];
        if (!is_array($endpoints)) $endpoints = [];
        $up = 0; $down = 0; $uptimeSum = 0;
        foreach ($endpoints as $ep) {
            $results = $ep['results'] ?? [];
            if (is_array($results) && count($results) > 0) {
                $last = end($results);
                if ($last['success'] ?? false) $up++;
                else $down++;
            }
            $uptimeSum += (float)($ep['uptime'] ?? 0);
        }
        $total = count($endpoints);
        $avgUptime = $total > 0 ? round($uptimeSum / $total * 100, 1) : 0;
        return [
            'up' => (string)$up,
            'down' => (string)$down,
            'uptime' => $avgUptime . '%',
        ];
    }
}
