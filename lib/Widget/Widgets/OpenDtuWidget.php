<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class OpenDtuWidget extends AbstractWidget {

    public function getId(): string { return 'opendtu'; }
    public function getLabel(): string { return 'OpenDTU'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['power', 'yieldDay', 'yieldTotal']; }

    public function getFieldLabels(): array {
        return ['power' => 'Power', 'yieldDay' => 'Today', 'yieldTotal' => 'Total'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/livedata/status'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0]['total'] ?? $responses[0] ?? [];
        $power = round((float)($data['Power']['v'] ?? 0));
        $yieldDay = round((float)($data['YieldDay']['v'] ?? 0), 2);
        $yieldTotal = round((float)($data['YieldTotal']['v'] ?? 0), 2);
        return [
            'power' => $power . ' W',
            'yieldDay' => $yieldDay . ' kWh',
            'yieldTotal' => $yieldTotal . ' kWh',
        ];
    }
}
