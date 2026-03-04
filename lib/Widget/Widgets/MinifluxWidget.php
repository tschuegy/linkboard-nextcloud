<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class MinifluxWidget extends AbstractWidget {

    public function getId(): string { return 'miniflux'; }
    public function getLabel(): string { return 'Miniflux'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['unread', 'feeds', 'readToday']; }

    public function getFieldLabels(): array {
        return ['unread' => 'Unread', 'feeds' => 'Feeds', 'readToday' => 'Read Today'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Auth-Token: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/v1/feeds/counters', 'headers' => $headers],
            ['url' => $base . '/v1/feeds', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $counters = $responses[0]['unreads'] ?? [];
        $unread = 0;
        if (is_array($counters)) {
            foreach ($counters as $count) {
                $unread += (int)$count;
            }
        }
        $feeds = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $reads = $responses[0]['reads'] ?? [];
        $readToday = 0;
        if (is_array($reads)) {
            foreach ($reads as $count) {
                $readToday += (int)$count;
            }
        }
        return [
            'unread' => (string)$unread,
            'feeds' => (string)$feeds,
            'readToday' => (string)$readToday,
        ];
    }
}
