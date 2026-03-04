<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ApcUpsWidget extends AbstractWidget {

    public function getId(): string { return 'apcups'; }
    public function getLabel(): string { return 'APC UPS'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['status', 'battery', 'load', 'runtime']; }

    public function getFieldLabels(): array {
        return ['status' => 'Status', 'battery' => 'Battery', 'load' => 'Load', 'runtime' => 'Runtime'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/v1/ups'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        if (isset($data[0])) $data = $data[0];
        $runtime = (int)($data['battery.runtime'] ?? $data['TIMELEFT'] ?? 0);
        $minutes = round($runtime / 60);
        return [
            'status' => (string)($data['ups.status'] ?? $data['STATUS'] ?? 'unknown'),
            'battery' => (string)($data['battery.charge'] ?? $data['BCHARGE'] ?? 0) . '%',
            'load' => (string)($data['ups.load'] ?? $data['LOADPCT'] ?? 0) . '%',
            'runtime' => $minutes . ' min',
        ];
    }
}
