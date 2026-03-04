<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TraefikWidget extends AbstractWidget {

    public function getId(): string { return 'traefik'; }
    public function getLabel(): string { return 'Traefik'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'placeholder' => 'Optional'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['routers', 'services', 'middleware']; }

    public function getFieldLabels(): array {
        return ['routers' => 'Routers', 'services' => 'Services', 'middleware' => 'Middleware'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['username'])) {
            $headers[] = 'Authorization: Basic ' . base64_encode($config['username'] . ':' . ($config['password'] ?? ''));
        }
        return [
            ['url' => $base . '/api/overview', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $http = $data['http'] ?? [];
        $routers = (int)($http['routers']['total'] ?? 0);
        $services = (int)($http['services']['total'] ?? 0);
        $middleware = (int)($http['middlewares']['total'] ?? 0);
        return [
            'routers' => (string)$routers,
            'services' => (string)$services,
            'middleware' => (string)$middleware,
        ];
    }
}
