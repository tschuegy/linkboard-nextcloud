<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PrometheusWidget extends AbstractWidget {

    public function getId(): string { return 'prometheus'; }
    public function getLabel(): string { return 'Prometheus'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Bearer Token', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['targetsUp', 'targetsDown', 'totalSeries']; }

    public function getFieldLabels(): array {
        return ['targetsUp' => 'Up', 'targetsDown' => 'Down', 'totalSeries' => 'Series'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $config['api_key'];
        }
        return [
            ['url' => $base . '/api/v1/targets', 'headers' => $headers],
            ['url' => $base . '/api/v1/status/tsdb', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $targets = $responses[0]['data']['activeTargets'] ?? [];
        $up = 0; $down = 0;
        if (is_array($targets)) {
            foreach ($targets as $t) {
                if (($t['health'] ?? '') === 'up') $up++;
                else $down++;
            }
        }
        $series = $responses[1]['data']['headStats']['numSeries'] ?? 0;
        return [
            'targetsUp' => (string)$up,
            'targetsDown' => (string)$down,
            'totalSeries' => (string)$series,
        ];
    }
}
