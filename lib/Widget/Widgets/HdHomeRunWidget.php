<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class HdHomeRunWidget extends AbstractWidget {

    public function getId(): string { return 'hdhomerun'; }
    public function getLabel(): string { return 'HDHomeRun'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['channels', 'tuners', 'activeStreams']; }

    public function getFieldLabels(): array {
        return ['channels' => 'Channels', 'tuners' => 'Tuners', 'activeStreams' => 'Active'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/lineup.json'],
            ['url' => $base . '/status.json'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $channels = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $status = $responses[1] ?? [];
        $tuners = is_array($status['tuners'] ?? null) ? count($status['tuners']) : 0;
        $active = 0;
        if (is_array($status['tuners'] ?? null)) {
            foreach ($status['tuners'] as $t) {
                if (!empty($t['VctNumber'])) $active++;
            }
        }
        return [
            'channels' => (string)$channels,
            'tuners' => (string)$tuners,
            'activeStreams' => (string)$active,
        ];
    }
}
