<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class FreshRssWidget extends AbstractWidget {

    public function getId(): string { return 'freshrss'; }
    public function getLabel(): string { return 'FreshRSS'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'API Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['unread', 'feeds', 'total']; }

    public function getFieldLabels(): array {
        return ['unread' => 'Unread', 'feeds' => 'Feeds', 'total' => 'Total'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/api/greader.php/reader/api/0/unread-count?output=json', 'headers' => $headers],
            ['url' => $base . '/api/greader.php/reader/api/0/subscription/list?output=json', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $unreadData = $responses[0]['unreadcounts'] ?? [];
        $unread = 0;
        if (is_array($unreadData)) {
            foreach ($unreadData as $item) {
                if (($item['id'] ?? '') === 'user/-/state/com.google/reading-list') {
                    $unread = (int)($item['count'] ?? 0);
                    break;
                }
            }
        }
        $subs = $responses[1]['subscriptions'] ?? [];
        $feeds = is_array($subs) ? count($subs) : 0;
        return [
            'unread' => (string)$unread,
            'feeds' => (string)$feeds,
            'total' => (string)$unread,
        ];
    }
}
