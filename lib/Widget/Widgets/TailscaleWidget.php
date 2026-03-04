<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TailscaleWidget extends AbstractWidget {

    public function getId(): string { return 'tailscale'; }
    public function getLabel(): string { return 'Tailscale'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'tailnet', 'label' => 'Tailnet', 'type' => 'text', 'required' => true, 'placeholder' => 'your-tailnet'],
        ];
    }

    public function getAllowedFields(): array { return ['devices', 'online']; }

    public function getFieldLabels(): array {
        return ['devices' => 'Devices', 'online' => 'Online'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $tailnet = $config['tailnet'] ?? '';
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => 'https://api.tailscale.com/api/v2/tailnet/' . $tailnet . '/devices', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $devices = $responses[0]['devices'] ?? [];
        if (!is_array($devices)) $devices = [];
        $online = 0;
        foreach ($devices as $d) {
            if (!empty($d['online'])) $online++;
        }
        return [
            'devices' => (string)count($devices),
            'online' => (string)$online,
        ];
    }
}
