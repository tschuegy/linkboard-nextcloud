<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class BookloreWidget extends AbstractWidget {

    public function getId(): string { return 'booklore'; }
    public function getLabel(): string { return 'Booklore'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['books', 'shelves']; }

    public function getFieldLabels(): array {
        return ['books' => 'Books', 'shelves' => 'Shelves'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['api_key'])) {
            $headers[] = 'X-Api-Key: ' . $config['api_key'];
        }
        return [
            ['url' => $base . '/api/books', 'headers' => $headers],
            ['url' => $base . '/api/shelves', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $books = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $shelves = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        return [
            'books' => (string)$books,
            'shelves' => (string)$shelves,
        ];
    }
}
