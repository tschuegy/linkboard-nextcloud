<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class HomeAssistantWidget extends AbstractWidget {

    public function getId(): string { return 'homeassistant'; }
    public function getLabel(): string { return 'Home Assistant'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'token', 'label' => 'Long-Lived Access Token', 'type' => 'password', 'required' => true, 'placeholder' => 'eyJ...'],
        ];
    }

    public function getAllowedFields(): array { return ['entities', 'automations', 'lights']; }

    public function getFieldLabels(): array {
        return ['entities' => 'Entities', 'automations' => 'Automations', 'lights' => 'Lights'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        return [[
            'url' => rtrim($baseUrl, '/') . '/api/states',
            'headers' => [
                'Authorization: Bearer ' . ($config['token'] ?? ''),
                'Content-Type: application/json',
            ],
        ]];
    }

    public function mapResponse(array $responses, array $config): array {
        $states = $responses[0] ?? [];
        if (!is_array($states)) $states = [];

        $entities = count($states);
        $automations = 0;
        $lights = 0;

        foreach ($states as $entity) {
            $id = $entity['entity_id'] ?? '';
            if (str_starts_with($id, 'automation.')) $automations++;
            elseif (str_starts_with($id, 'light.')) $lights++;
        }

        return [
            'entities' => (string)$entities,
            'automations' => (string)$automations,
            'lights' => (string)$lights,
        ];
    }
}
