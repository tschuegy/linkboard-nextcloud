<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class CaddyWidget extends AbstractWidget {

    public function getId(): string { return 'caddy'; }
    public function getLabel(): string { return 'Caddy'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['upstreams', 'routes', 'status']; }

    public function getFieldLabels(): array {
        return ['upstreams' => 'Upstreams', 'routes' => 'Routes', 'status' => 'Status'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/config/'],
            ['url' => $base . '/reverse_proxy/upstreams'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $config = $responses[0] ?? [];
        $upstreams = $responses[1] ?? [];
        $routes = 0;
        $apps = $config['apps']['http']['servers'] ?? [];
        if (is_array($apps)) {
            foreach ($apps as $server) {
                $routes += count($server['routes'] ?? []);
            }
        }
        $upstreamCount = is_array($upstreams) ? count($upstreams) : 0;
        return [
            'upstreams' => (string)$upstreamCount,
            'routes' => (string)$routes,
            'status' => 'running',
        ];
    }
}
