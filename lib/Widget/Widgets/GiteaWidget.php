<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class GiteaWidget extends AbstractWidget {

    public function getId(): string { return 'gitea'; }
    public function getLabel(): string { return 'Gitea'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['repos', 'users', 'orgs', 'issues']; }

    public function getFieldLabels(): array {
        return ['repos' => 'Repos', 'users' => 'Users', 'orgs' => 'Orgs', 'issues' => 'Issues'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: token ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/repos/search?limit=1', 'headers' => $headers],
            ['url' => $base . '/api/v1/admin/users?limit=1', 'headers' => $headers],
            ['url' => $base . '/api/v1/admin/orgs?limit=1', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        // Gitea returns X-Total-Count header but we get JSON body; use search results count
        $repos = is_array($responses[0]['data'] ?? null) ? ($responses[0]['data'][0]['id'] ?? 0) : 0;
        $users = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $orgs = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        return [
            'repos' => (string)$repos,
            'users' => (string)$users,
            'orgs' => (string)$orgs,
            'issues' => '0',
        ];
    }
}
