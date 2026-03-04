<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class CalibreWebWidget extends AbstractWidget {

    public function getId(): string { return 'calibreweb'; }
    public function getLabel(): string { return 'Calibre-Web'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['books', 'authors', 'categories']; }

    public function getFieldLabels(): array {
        return ['books' => 'Books', 'authors' => 'Authors', 'categories' => 'Categories'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/opds', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'books' => (string)($data['totalBooks'] ?? 0),
            'authors' => (string)($data['totalAuthors'] ?? 0),
            'categories' => (string)($data['totalCategories'] ?? 0),
        ];
    }
}
