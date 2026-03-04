<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class JellyseerrWidget extends AbstractWidget {

    public function getId(): string { return 'jellyseerr'; }
    public function getLabel(): string { return 'Jellyseerr'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['pending', 'approved', 'available', 'issues']; }

    public function getFieldLabels(): array {
        return ['pending' => 'Pending', 'approved' => 'Approved', 'available' => 'Available', 'issues' => 'Issues'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Api-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/request/count', 'headers' => $headers],
            ['url' => $base . '/api/v1/issue?take=1', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $issues = $responses[1]['pageInfo']['results'] ?? 0;
        return [
            'pending' => (string)($data['pending'] ?? 0),
            'approved' => (string)($data['approved'] ?? 0),
            'available' => (string)($data['available'] ?? 0),
            'issues' => (string)$issues,
        ];
    }
}
