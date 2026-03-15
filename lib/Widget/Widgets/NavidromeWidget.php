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
        $r0 = $responses[0] ?? [];
        $r1 = $responses[1] ?? [];
        $r2 = $responses[2] ?? [];
        return [
            'songs' => (string)($r0['x-total-count'] ?? (is_array($r0) ? count($r0) : 0)),
            'albums' => (string)($r1['x-total-count'] ?? (is_array($r1) ? count($r1) : 0)),
            'artists' => (string)($r2['x-total-count'] ?? (is_array($r2) ? count($r2) : 0)),
        ];
    }
}
