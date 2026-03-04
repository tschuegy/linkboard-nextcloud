<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class KomodoWidget extends AbstractWidget {

    public function getId(): string { return 'komodo'; }
    public function getLabel(): string { return 'Komodo'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['servers', 'deployments', 'builds']; }

    public function getFieldLabels(): array {
        return ['servers' => 'Servers', 'deployments' => 'Deployments', 'builds' => 'Builds'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/servers', 'headers' => $headers],
            ['url' => $base . '/api/deployments', 'headers' => $headers],
            ['url' => $base . '/api/builds', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $servers = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $deployments = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $builds = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        return [
            'servers' => (string)$servers,
            'deployments' => (string)$deployments,
            'builds' => (string)$builds,
        ];
    }
}
