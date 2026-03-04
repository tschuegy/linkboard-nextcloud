<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class OmbiWidget extends AbstractWidget {

    public function getId(): string { return 'ombi'; }
    public function getLabel(): string { return 'Ombi'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['pending', 'approved', 'available']; }

    public function getFieldLabels(): array {
        return ['pending' => 'Pending', 'approved' => 'Approved', 'available' => 'Available'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['ApiKey: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/Request/count', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'pending' => (string)($data['pending'] ?? 0),
            'approved' => (string)($data['approved'] ?? 0),
            'available' => (string)($data['available'] ?? 0),
        ];
    }
}
