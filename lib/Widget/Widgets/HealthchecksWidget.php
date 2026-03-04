<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class HealthchecksWidget extends AbstractWidget {

    public function getId(): string { return 'healthchecks'; }
    public function getLabel(): string { return 'Healthchecks'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Read-only API key'],
        ];
    }

    public function getAllowedFields(): array { return ['up', 'down', 'grace', 'paused']; }

    public function getFieldLabels(): array {
        return ['up' => 'Up', 'down' => 'Down', 'grace' => 'Grace', 'paused' => 'Paused'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Api-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v3/checks/', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $checks = $responses[0]['checks'] ?? [];
        $up = 0; $down = 0; $grace = 0; $paused = 0;
        if (is_array($checks)) {
            foreach ($checks as $c) {
                $status = $c['status'] ?? '';
                if ($status === 'up') $up++;
                elseif ($status === 'down') $down++;
                elseif ($status === 'grace') $grace++;
                elseif ($status === 'paused') $paused++;
            }
        }
        return [
            'up' => (string)$up,
            'down' => (string)$down,
            'grace' => (string)$grace,
            'paused' => (string)$paused,
        ];
    }
}
