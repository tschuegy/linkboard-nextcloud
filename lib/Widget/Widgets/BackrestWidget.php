<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class BackrestWidget extends AbstractWidget {

    public function getId(): string { return 'backrest'; }
    public function getLabel(): string { return 'Backrest'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['plans', 'snapshots', 'status']; }

    public function getFieldLabels(): array {
        return ['plans' => 'Plans', 'snapshots' => 'Snapshots', 'status' => 'Status'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $config['api_key'];
        }
        return [
            ['url' => $base . '/api/v1/config', 'headers' => $headers],
            ['url' => $base . '/api/v1/operations', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $cfg = $responses[0] ?? [];
        $plans = is_array($cfg['plans'] ?? null) ? count($cfg['plans']) : 0;
        $ops = $responses[1] ?? [];
        $snapshots = 0;
        if (is_array($ops)) {
            foreach ($ops as $op) {
                if (($op['type'] ?? '') === 'snapshot') $snapshots++;
            }
        }
        return [
            'plans' => (string)$plans,
            'snapshots' => (string)$snapshots,
            'status' => 'ok',
        ];
    }
}
