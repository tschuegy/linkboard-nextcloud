<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class NpmWidget extends AbstractWidget {

    public function getId(): string { return 'npm'; }
    public function getLabel(): string { return 'Nginx Proxy Manager'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'required' => true, 'placeholder' => 'admin@example.com'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['proxies', 'redirects', 'streams']; }

    public function getFieldLabels(): array {
        return ['proxies' => 'Proxies', 'redirects' => 'Redirects', 'streams' => 'Streams'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        // NPM requires a login step first; we handle this specially
        // by making a token request, then the proxy-hosts request.
        // The controller will handle the two-step auth.
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/tokens',
                'method' => 'POST',
                'body' => json_encode([
                    'identity' => $config['email'] ?? '',
                    'secret' => $config['password'] ?? '',
                ]),
                'headers' => ['Content-Type: application/json'],
                '_npm_login' => true,
            ],
            [
                'url' => $base . '/api/nginx/proxy-hosts',
                'headers' => [],
                '_npm_needs_token' => true,
            ],
            [
                'url' => $base . '/api/nginx/redirection-hosts',
                'headers' => [],
                '_npm_needs_token' => true,
            ],
            [
                'url' => $base . '/api/nginx/streams',
                'headers' => [],
                '_npm_needs_token' => true,
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        // responses[0] = token response (skipped in counting)
        $proxies = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $redirects = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        $streams = is_array($responses[3] ?? null) ? count($responses[3]) : 0;

        return [
            'proxies' => (string)$proxies,
            'redirects' => (string)$redirects,
            'streams' => (string)$streams,
        ];
    }
}
