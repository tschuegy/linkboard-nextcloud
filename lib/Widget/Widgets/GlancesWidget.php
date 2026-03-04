<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class GlancesWidget extends AbstractWidget {

    public function getId(): string { return 'glances'; }
    public function getLabel(): string { return 'Glances'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['cpu', 'mem', 'swap']; }

    public function getFieldLabels(): array {
        return ['cpu' => 'CPU', 'mem' => 'Memory', 'swap' => 'Swap'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/4/quicklook'],
            ['url' => $base . '/api/4/mem'],
            ['url' => $base . '/api/4/memswap'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $cpu = round((float)($responses[0]['cpu'] ?? 0), 1);
        $mem = round((float)($responses[1]['percent'] ?? 0), 1);
        $swap = round((float)($responses[2]['percent'] ?? 0), 1);
        return [
            'cpu' => $cpu . '%',
            'mem' => $mem . '%',
            'swap' => $swap . '%',
        ];
    }
}
