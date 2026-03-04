<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PeanutWidget extends AbstractWidget {

    public function getId(): string { return 'peanut'; }
    public function getLabel(): string { return 'Peanut (NUT UPS)'; }

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
        // Peanut may return array of UPS or single UPS object
        if (isset($data[0])) $data = $data[0];
        $runtime = (int)($data['battery.runtime'] ?? 0);
        $minutes = round($runtime / 60);
        return [
            'status' => (string)($data['ups.status'] ?? 'unknown'),
            'battery' => (string)($data['battery.charge'] ?? 0) . '%',
            'load' => (string)($data['ups.load'] ?? 0) . '%',
            'runtime' => $minutes . ' min',
        ];
    }
}
