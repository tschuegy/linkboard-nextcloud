<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class NavidromeWidget extends AbstractWidget {

    public function getId(): string { return 'navidrome'; }
    public function getLabel(): string { return 'Navidrome'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['songs', 'albums', 'artists']; }

    public function getFieldLabels(): array {
        return ['songs' => 'Songs', 'albums' => 'Albums', 'artists' => 'Artists'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/api/song?_end=0', 'headers' => $headers],
            ['url' => $base . '/api/album?_end=0', 'headers' => $headers],
            ['url' => $base . '/api/artist?_end=0', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        return [
            'songs' => (string)($responses[0]['x-total-count'] ?? (is_array($responses[0]) ? count($responses[0]) : 0)),
            'albums' => (string)($responses[1]['x-total-count'] ?? (is_array($responses[1]) ? count($responses[1]) : 0)),
            'artists' => (string)($responses[2]['x-total-count'] ?? (is_array($responses[2]) ? count($responses[2]) : 0)),
        ];
    }
}
