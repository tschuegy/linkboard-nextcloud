<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class AdGuardWidget extends AbstractWidget {

    public function getId(): string { return 'adguard'; }
    public function getLabel(): string { return 'AdGuard Home'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => 'admin'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['queries', 'blocked', 'filtered']; }

    public function getFieldLabels(): array {
        return ['queries' => 'Queries', 'blocked' => 'Blocked', 'filtered' => 'Filtered %'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $auth = base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        return [[
            'url' => rtrim($baseUrl, '/') . '/control/stats',
            'headers' => ['Authorization: Basic ' . $auth],
        ]];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $queries = (int)($data['num_dns_queries'] ?? 0);
        $blocked = (int)($data['num_blocked_filtering'] ?? 0);
        $pct = $queries > 0 ? round($blocked / $queries * 100, 1) : 0;

        return [
            'queries' => number_format($queries),
            'blocked' => number_format($blocked),
            'filtered' => $pct . '%',
        ];
    }
}
