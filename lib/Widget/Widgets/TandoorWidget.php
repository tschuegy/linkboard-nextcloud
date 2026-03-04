<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TandoorWidget extends AbstractWidget {

    public function getId(): string { return 'tandoor'; }
    public function getLabel(): string { return 'Tandoor'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['recipes', 'keywords', 'shoppingLists']; }

    public function getFieldLabels(): array {
        return ['recipes' => 'Recipes', 'keywords' => 'Keywords', 'shoppingLists' => 'Shopping Lists'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/recipe/?page=1&page_size=1', 'headers' => $headers],
            ['url' => $base . '/api/keyword/?page=1&page_size=1', 'headers' => $headers],
            ['url' => $base . '/api/shopping-list/?page=1&page_size=1', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        return [
            'recipes' => (string)($responses[0]['count'] ?? 0),
            'keywords' => (string)($responses[1]['count'] ?? 0),
            'shoppingLists' => (string)($responses[2]['count'] ?? 0),
        ];
    }
}
