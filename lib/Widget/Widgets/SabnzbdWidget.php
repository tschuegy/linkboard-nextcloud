<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class SabnzbdWidget extends AbstractWidget {

    public function getId(): string { return 'sabnzbd'; }
    public function getLabel(): string { return 'SABnzbd'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['rate', 'queue', 'timeleft']; }

    public function getFieldLabels(): array {
        return ['rate' => 'Speed', 'queue' => 'Queue', 'timeleft' => 'Time Left'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $key = $config['api_key'] ?? '';
        return [
            ['url' => $base . '/api?mode=queue&output=json&apikey=' . $key],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $queue = $responses[0]['queue'] ?? [];
        return [
            'rate' => (string)($queue['speed'] ?? '0 B/s'),
            'queue' => (string)($queue['noofslots'] ?? 0),
            'timeleft' => (string)($queue['timeleft'] ?? '0:00:00'),
        ];
    }
}
