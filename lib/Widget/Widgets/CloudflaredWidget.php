<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class CloudflaredWidget extends AbstractWidget {

    public function getId(): string { return 'cloudflared'; }
    public function getLabel(): string { return 'Cloudflared'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'account_id', 'label' => 'Account ID', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'tunnel_id', 'label' => 'Tunnel ID', 'type' => 'text', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['status', 'originIp']; }

    public function getFieldLabels(): array {
        return ['status' => 'Status', 'originIp' => 'Origin IP'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $accountId = $config['account_id'] ?? '';
        $tunnelId = $config['tunnel_id'] ?? '';
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => 'https://api.cloudflare.com/client/v4/accounts/' . $accountId . '/cfd_tunnel/' . $tunnelId, 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $result = $responses[0]['result'] ?? [];
        $status = $result['status'] ?? 'unknown';
        $conns = $result['connections'] ?? [];
        $originIp = '';
        if (is_array($conns) && count($conns) > 0) {
            $originIp = $conns[0]['origin_ip'] ?? '';
        }
        return [
            'status' => (string)$status,
            'originIp' => (string)$originIp,
        ];
    }
}
