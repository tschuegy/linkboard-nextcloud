<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class CrowdSecWidget extends AbstractWidget {

    public function getId(): string { return 'crowdsec'; }
    public function getLabel(): string { return 'CrowdSec'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Local API key'],
        ];
    }

    public function getAllowedFields(): array { return ['alerts', 'bans', 'bouncers']; }

    public function getFieldLabels(): array {
        return ['alerts' => 'Alerts', 'bans' => 'Bans', 'bouncers' => 'Bouncers'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Api-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/v1/alerts?limit=0', 'headers' => $headers],
            ['url' => $base . '/v1/decisions', 'headers' => $headers],
            ['url' => $base . '/v1/bouncers', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $alerts = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $bans = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $bouncers = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        return [
            'alerts' => (string)$alerts,
            'bans' => (string)$bans,
            'bouncers' => (string)$bouncers,
        ];
    }
}
