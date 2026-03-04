<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ReadarrWidget extends AbstractWidget {

    public function getId(): string { return 'readarr'; }
    public function getLabel(): string { return 'Readarr'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['wanted', 'queued', 'books']; }

    public function getFieldLabels(): array {
        return ['wanted' => 'Wanted', 'queued' => 'Queued', 'books' => 'Books'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Api-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/wanted/missing?pageSize=1', 'headers' => $headers],
            ['url' => $base . '/api/v1/queue/status', 'headers' => $headers],
            ['url' => $base . '/api/v1/book', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $wanted = $responses[0]['totalRecords'] ?? 0;
        $queued = $responses[1]['totalCount'] ?? 0;
        $books = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        return [
            'wanted' => (string)$wanted,
            'queued' => (string)$queued,
            'books' => (string)$books,
        ];
    }
}
