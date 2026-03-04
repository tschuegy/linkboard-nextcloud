<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class SpeedtestTrackerWidget extends AbstractWidget {

    public function getId(): string { return 'speedtesttracker'; }
    public function getLabel(): string { return 'Speedtest Tracker'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional for v2'],
        ];
    }

    public function getAllowedFields(): array { return ['download', 'upload', 'ping']; }

    public function getFieldLabels(): array {
        return ['download' => 'Download', 'upload' => 'Upload', 'ping' => 'Ping'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $config['api_key'];
        }
        $headers[] = 'Accept: application/json';
        return [
            ['url' => $base . '/api/v1/results/latest', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0]['data'] ?? $responses[0] ?? [];
        $down = round((float)($data['download'] ?? 0), 1);
        $up = round((float)($data['upload'] ?? 0), 1);
        $ping = round((float)($data['ping'] ?? 0), 1);
        return [
            'download' => $down . ' Mbps',
            'upload' => $up . ' Mbps',
            'ping' => $ping . ' ms',
        ];
    }
}
