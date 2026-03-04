<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ArgoCdWidget extends AbstractWidget {

    public function getId(): string { return 'argocd'; }
    public function getLabel(): string { return 'ArgoCD'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Auth Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['apps', 'synced', 'healthy', 'degraded']; }

    public function getFieldLabels(): array {
        return ['apps' => 'Apps', 'synced' => 'Synced', 'healthy' => 'Healthy', 'degraded' => 'Degraded'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/applications', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $items = $responses[0]['items'] ?? [];
        if (!is_array($items)) $items = [];
        $synced = 0; $healthy = 0; $degraded = 0;
        foreach ($items as $app) {
            $syncStatus = $app['status']['sync']['status'] ?? '';
            $healthStatus = $app['status']['health']['status'] ?? '';
            if ($syncStatus === 'Synced') $synced++;
            if ($healthStatus === 'Healthy') $healthy++;
            if ($healthStatus === 'Degraded') $degraded++;
        }
        return [
            'apps' => (string)count($items),
            'synced' => (string)$synced,
            'healthy' => (string)$healthy,
            'degraded' => (string)$degraded,
        ];
    }
}
