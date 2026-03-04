<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class MealieWidget extends AbstractWidget {

    public function getId(): string { return 'mealie'; }
    public function getLabel(): string { return 'Mealie'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['recipes', 'mealplans', 'shoppingLists']; }

    public function getFieldLabels(): array {
        return ['recipes' => 'Recipes', 'mealplans' => 'Meal Plans', 'shoppingLists' => 'Shopping Lists'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/recipes?perPage=1&page=1', 'headers' => $headers],
            ['url' => $base . '/api/groups/mealplans?perPage=1&page=1', 'headers' => $headers],
            ['url' => $base . '/api/groups/shopping/lists', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $recipes = $responses[0]['total'] ?? 0;
        $mealplans = $responses[1]['total'] ?? 0;
        $shoppingLists = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        return [
            'recipes' => (string)$recipes,
            'mealplans' => (string)$mealplans,
            'shoppingLists' => (string)$shoppingLists,
        ];
    }
}
