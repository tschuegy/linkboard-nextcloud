<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class OverseerrWidget extends AbstractWidget {

    public function getId(): string { return 'overseerr'; }
    public function getLabel(): string { return 'Overseerr'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['pending', 'approved', 'available', 'processing']; }

    public function getFieldLabels(): array {
        return ['pending' => 'Pending', 'approved' => 'Approved', 'available' => 'Available', 'processing' => 'Processing'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Api-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/request/count', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'pending' => (string)($data['pending'] ?? 0),
            'approved' => (string)($data['approved'] ?? 0),
            'available' => (string)($data['available'] ?? 0),
            'processing' => (string)($data['processing'] ?? 0),
        ];
    }
}
