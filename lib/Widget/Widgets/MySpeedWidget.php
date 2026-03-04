<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class MySpeedWidget extends AbstractWidget {

    public function getId(): string { return 'myspeed'; }
    public function getLabel(): string { return 'MySpeed'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['download', 'upload', 'ping']; }

    public function getFieldLabels(): array {
        return ['download' => 'Download', 'upload' => 'Upload', 'ping' => 'Ping'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/speedtests/latest'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'download' => round((float)($data['download'] ?? 0), 1) . ' Mbps',
            'upload' => round((float)($data['upload'] ?? 0), 1) . ' Mbps',
            'ping' => round((float)($data['ping'] ?? 0), 1) . ' ms',
        ];
    }
}
