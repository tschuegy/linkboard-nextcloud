<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PiHoleWidget extends AbstractWidget {

    public function getId(): string { return 'pihole'; }
    public function getLabel(): string { return 'Pi-hole'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_token', 'label' => 'API Token', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional for public stats'],
        ];
    }

    public function getAllowedFields(): array { return ['queries', 'blocked', 'percentage']; }

    public function getFieldLabels(): array {
        return ['queries' => 'Queries', 'blocked' => 'Blocked', 'percentage' => 'Blocked %'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $auth = !empty($config['api_token']) ? '&auth=' . $config['api_token'] : '';
        return [[
            'url' => rtrim($baseUrl, '/') . '/admin/api.php?summaryRaw' . $auth,
        ]];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'queries' => number_format((float)($data['dns_queries_today'] ?? 0)),
            'blocked' => number_format((float)($data['ads_blocked_today'] ?? 0)),
            'percentage' => round((float)($data['ads_percentage_today'] ?? 0), 1) . '%',
        ];
    }
}
