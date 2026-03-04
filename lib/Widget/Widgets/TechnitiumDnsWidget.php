<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TechnitiumDnsWidget extends AbstractWidget {

    public function getId(): string { return 'technitiumdns'; }
    public function getLabel(): string { return 'Technitium DNS'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['queries', 'blocked', 'clients']; }

    public function getFieldLabels(): array {
        return ['queries' => 'Queries', 'blocked' => 'Blocked', 'clients' => 'Clients'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $key = $config['api_key'] ?? '';
        return [
            ['url' => $base . '/api/dashboard/stats/get?token=' . $key . '&type=lastHour'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats = $responses[0]['response']['stats'] ?? $responses[0]['response'] ?? [];
        return [
            'queries' => (string)($stats['totalQueries'] ?? 0),
            'blocked' => (string)($stats['totalBlocked'] ?? 0),
            'clients' => (string)($stats['totalClients'] ?? 0),
        ];
    }
}
