<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class AuthentikWidget extends AbstractWidget {

    public function getId(): string { return 'authentik'; }
    public function getLabel(): string { return 'Authentik'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['users', 'loginsLast24H', 'failedLoginsLast24H']; }

    public function getFieldLabels(): array {
        return ['users' => 'Users', 'loginsLast24H' => 'Logins 24h', 'failedLoginsLast24H' => 'Failed 24h'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v3/core/users/?page_size=1', 'headers' => $headers],
            ['url' => $base . '/api/v3/events/events/?action=login&page_size=1&ordering=-created', 'headers' => $headers],
            ['url' => $base . '/api/v3/events/events/?action=login_failed&page_size=1&ordering=-created', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $users = $responses[0]['pagination']['count'] ?? 0;
        $logins = $responses[1]['pagination']['count'] ?? 0;
        $failed = $responses[2]['pagination']['count'] ?? 0;
        return [
            'users' => (string)$users,
            'loginsLast24H' => (string)$logins,
            'failedLoginsLast24H' => (string)$failed,
        ];
    }
}
