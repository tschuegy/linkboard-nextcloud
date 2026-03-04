<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class UnraidWidget extends AbstractWidget {

    public function getId(): string { return 'unraid'; }
    public function getLabel(): string { return 'Unraid'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['cpuUsage', 'memUsage', 'arrayStatus']; }

    public function getFieldLabels(): array {
        return ['cpuUsage' => 'CPU', 'memUsage' => 'Memory', 'arrayStatus' => 'Array'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/dashboard', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'cpuUsage' => round((float)($data['cpu'] ?? 0)) . '%',
            'memUsage' => round((float)($data['memory'] ?? 0)) . '%',
            'arrayStatus' => (string)($data['arrayStatus'] ?? 'unknown'),
        ];
    }
}
