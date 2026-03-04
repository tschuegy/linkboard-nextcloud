<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class UptimeKumaWidget extends AbstractWidget {

    public function getId(): string { return 'uptimekuma'; }
    public function getLabel(): string { return 'Uptime Kuma'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'slug', 'label' => 'Status Page Slug', 'type' => 'text', 'required' => true, 'placeholder' => 'default'],
        ];
    }

    public function getAllowedFields(): array { return ['up', 'down', 'uptime']; }

    public function getFieldLabels(): array {
        return ['up' => 'Up', 'down' => 'Down', 'uptime' => 'Uptime'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $slug = $config['slug'] ?? 'default';
        return [[
            'url' => rtrim($baseUrl, '/') . '/api/status-page/' . $slug,
        ]];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $heartbeatList = $data['heartbeatList'] ?? [];
        $up = 0; $down = 0; $total = 0;

        foreach ($heartbeatList as $monitorId => $beats) {
            if (!is_array($beats) || empty($beats)) continue;
            $latest = end($beats);
            $total++;
            if (($latest['status'] ?? 0) === 1) $up++;
            else $down++;
        }

        $uptime = $total > 0 ? round($up / $total * 100, 1) . '%' : '0%';

        return [
            'up' => (string)$up,
            'down' => (string)$down,
            'uptime' => $uptime,
        ];
    }
}
