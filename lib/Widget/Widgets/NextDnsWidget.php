<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class NextDnsWidget extends AbstractWidget {

    public function getId(): string { return 'nextdns'; }
    public function getLabel(): string { return 'NextDNS'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'profile_id', 'label' => 'Profile ID', 'type' => 'text', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['queries', 'blocked', 'blockedPercent']; }

    public function getFieldLabels(): array {
        return ['queries' => 'Queries', 'blocked' => 'Blocked', 'blockedPercent' => 'Blocked %'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $profileId = $config['profile_id'] ?? '';
        $headers = ['X-Api-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => 'https://api.nextdns.io/profiles/' . $profileId . '/analytics/status', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0]['data'] ?? [];
        $queries = 0; $blocked = 0;
        if (is_array($data)) {
            foreach ($data as $item) {
                $queries += (int)($item['queries'] ?? 0);
                if (($item['status'] ?? '') !== 'default') {
                    $blocked += (int)($item['queries'] ?? 0);
                }
            }
        }
        $pct = $queries > 0 ? round($blocked / $queries * 100, 1) : 0;
        return [
            'queries' => (string)$queries,
            'blocked' => (string)$blocked,
            'blockedPercent' => $pct . '%',
        ];
    }
}
