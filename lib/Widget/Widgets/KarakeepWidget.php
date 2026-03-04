<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class KarakeepWidget extends AbstractWidget {

    public function getId(): string { return 'karakeep'; }
    public function getLabel(): string { return 'Karakeep'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['bookmarks', 'favorites', 'lists']; }

    public function getFieldLabels(): array {
        return ['bookmarks' => 'Bookmarks', 'favorites' => 'Favorites', 'lists' => 'Lists'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/bookmarks?limit=1', 'headers' => $headers],
            ['url' => $base . '/api/v1/lists', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $bookmarks = $responses[0]['totalCount'] ?? 0;
        $lists = is_array($responses[1]['lists'] ?? null) ? count($responses[1]['lists']) : 0;
        return [
            'bookmarks' => (string)$bookmarks,
            'favorites' => '0',
            'lists' => (string)$lists,
        ];
    }
}
