<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ArcaneWidget extends AbstractWidget {

    public function getId(): string { return 'arcane'; }
    public function getLabel(): string { return 'Arcane'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['accounts', 'proxies', 'tasks']; }

    public function getFieldLabels(): array {
        return ['accounts' => 'Accounts', 'proxies' => 'Proxies', 'tasks' => 'Tasks'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/stats', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'accounts' => (string)($data['accounts'] ?? 0),
            'proxies' => (string)($data['proxies'] ?? 0),
            'tasks' => (string)($data['tasks'] ?? 0),
        ];
    }
}
