<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PterodactylWidget extends AbstractWidget {

    public function getId(): string { return 'pterodactyl'; }
    public function getLabel(): string { return 'Pterodactyl'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Application API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['servers', 'users', 'nodes']; }

    public function getFieldLabels(): array {
        return ['servers' => 'Servers', 'users' => 'Users', 'nodes' => 'Nodes'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? ''), 'Accept: application/json'];
        return [
            ['url' => $base . '/api/application/servers', 'headers' => $headers],
            ['url' => $base . '/api/application/users', 'headers' => $headers],
            ['url' => $base . '/api/application/nodes', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $servers = $responses[0]['meta']['pagination']['total'] ?? 0;
        $users = $responses[1]['meta']['pagination']['total'] ?? 0;
        $nodes = $responses[2]['meta']['pagination']['total'] ?? 0;
        return [
            'servers' => (string)$servers,
            'users' => (string)$users,
            'nodes' => (string)$nodes,
        ];
    }
}
