<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class UptimeRobotWidget extends AbstractWidget {

    public function getId(): string { return 'uptimerobot'; }
    public function getLabel(): string { return 'UptimeRobot'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Read-only API key'],
        ];
    }

    public function getAllowedFields(): array { return ['up', 'down', 'paused']; }

    public function getFieldLabels(): array {
        return ['up' => 'Up', 'down' => 'Down', 'paused' => 'Paused'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        return [
            [
                'url' => 'https://api.uptimerobot.com/v2/getMonitors',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['api_key' => $config['api_key'] ?? '']),
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $monitors = $responses[0]['monitors'] ?? [];
        $up = 0; $down = 0; $paused = 0;
        if (is_array($monitors)) {
            foreach ($monitors as $m) {
                $status = (int)($m['status'] ?? 0);
                if ($status === 2) $up++;
                elseif ($status === 8 || $status === 9) $down++;
                elseif ($status === 0) $paused++;
            }
        }
        return [
            'up' => (string)$up,
            'down' => (string)$down,
            'paused' => (string)$paused,
        ];
    }
}
