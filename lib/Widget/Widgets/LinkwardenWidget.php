<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class LinkwardenWidget extends AbstractWidget {

    public function getId(): string { return 'linkwarden'; }
    public function getLabel(): string { return 'Linkwarden'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['links', 'collections', 'tags']; }

    public function getFieldLabels(): array {
        return ['links' => 'Links', 'collections' => 'Collections', 'tags' => 'Tags'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/links', 'headers' => $headers],
            ['url' => $base . '/api/v1/collections', 'headers' => $headers],
            ['url' => $base . '/api/v1/tags', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $links = is_array($responses[0]['response'] ?? null) ? count($responses[0]['response']) : 0;
        $collections = is_array($responses[1]['response'] ?? null) ? count($responses[1]['response']) : 0;
        $tags = is_array($responses[2]['response'] ?? null) ? count($responses[2]['response']) : 0;
        return [
            'links' => (string)$links,
            'collections' => (string)$collections,
            'tags' => (string)$tags,
        ];
    }
}
