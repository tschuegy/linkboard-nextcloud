<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class WatchtowerWidget extends AbstractWidget {

    public function getId(): string { return 'watchtower'; }
    public function getLabel(): string { return 'Watchtower'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['scanned', 'updated', 'failed']; }

    public function getFieldLabels(): array {
        return ['scanned' => 'Scanned', 'updated' => 'Updated', 'failed' => 'Failed'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/v1/update', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'scanned' => (string)($data['scanned'] ?? 0),
            'updated' => (string)($data['updated'] ?? 0),
            'failed' => (string)($data['failed'] ?? 0),
        ];
    }
}
