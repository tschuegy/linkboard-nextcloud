<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class EvccWidget extends AbstractWidget {

    public function getId(): string { return 'evcc'; }
    public function getLabel(): string { return 'EVCC'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['gridPower', 'pvPower', 'batteryPower', 'chargePower']; }

    public function getFieldLabels(): array {
        return ['gridPower' => 'Grid', 'pvPower' => 'PV', 'batteryPower' => 'Battery', 'chargePower' => 'Charge'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/state'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0]['result'] ?? $responses[0] ?? [];
        $grid = round((float)($data['gridPower'] ?? 0));
        $pv = round((float)($data['pvPower'] ?? 0));
        $battery = round((float)($data['batteryPower'] ?? 0));
        $charge = 0;
        $loadpoints = $data['loadpoints'] ?? [];
        if (is_array($loadpoints)) {
            foreach ($loadpoints as $lp) {
                $charge += (float)($lp['chargePower'] ?? 0);
            }
        }
        return [
            'gridPower' => $grid . ' W',
            'pvPower' => $pv . ' W',
            'batteryPower' => $battery . ' W',
            'chargePower' => round($charge) . ' W',
        ];
    }
}
