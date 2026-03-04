<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PlantItWidget extends AbstractWidget {

    public function getId(): string { return 'plantit'; }
    public function getLabel(): string { return 'Plant-It'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['plants', 'events', 'alerts']; }

    public function getFieldLabels(): array {
        return ['plants' => 'Plants', 'events' => 'Events', 'alerts' => 'Alerts'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/plant?pageSize=1', 'headers' => $headers],
            ['url' => $base . '/api/diary?pageSize=1', 'headers' => $headers],
            ['url' => $base . '/api/reminder', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $plants = $responses[0]['totalElements'] ?? 0;
        $events = $responses[1]['totalElements'] ?? 0;
        $alerts = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        return [
            'plants' => (string)$plants,
            'events' => (string)$events,
            'alerts' => (string)$alerts,
        ];
    }
}
