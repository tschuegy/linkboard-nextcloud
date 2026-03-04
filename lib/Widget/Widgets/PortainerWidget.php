<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PortainerWidget extends AbstractWidget {

    public function getId(): string { return 'portainer'; }
    public function getLabel(): string { return 'Portainer'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => 'ptr_...'],
            ['key' => 'env', 'label' => 'Environment ID', 'type' => 'text', 'required' => false, 'placeholder' => '1'],
        ];
    }

    public function getAllowedFields(): array { return ['running', 'stopped', 'total']; }

    public function getFieldLabels(): array {
        return ['running' => 'Running', 'stopped' => 'Stopped', 'total' => 'Total'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $env = $config['env'] ?? '1';
        return [[
            'url' => rtrim($baseUrl, '/') . '/api/endpoints/' . $env . '/docker/containers/json?all=true',
            'headers' => ['X-API-Key: ' . ($config['api_key'] ?? '')],
        ]];
    }

    public function mapResponse(array $responses, array $config): array {
        $containers = $responses[0] ?? [];
        if (!is_array($containers)) $containers = [];
        $running = 0; $stopped = 0;
        foreach ($containers as $c) {
            $state = $c['State'] ?? '';
            if ($state === 'running') $running++;
            else $stopped++;
        }
        return [
            'running' => (string)$running,
            'stopped' => (string)$stopped,
            'total' => (string)count($containers),
        ];
    }
}
