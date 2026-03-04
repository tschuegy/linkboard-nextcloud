<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class KomgaWidget extends AbstractWidget {

    public function getId(): string { return 'komga'; }
    public function getLabel(): string { return 'Komga'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['libraries', 'series', 'books']; }

    public function getFieldLabels(): array {
        return ['libraries' => 'Libraries', 'series' => 'Series', 'books' => 'Books'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/api/v1/libraries', 'headers' => $headers],
            ['url' => $base . '/api/v1/series?size=0', 'headers' => $headers],
            ['url' => $base . '/api/v1/books?size=0', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $libraries = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $series = $responses[1]['totalElements'] ?? 0;
        $books = $responses[2]['totalElements'] ?? 0;
        return [
            'libraries' => (string)$libraries,
            'series' => (string)$series,
            'books' => (string)$books,
        ];
    }
}
