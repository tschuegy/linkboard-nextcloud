<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class BeszelWidget extends AbstractWidget {

    public function getId(): string { return 'beszel'; }
    public function getLabel(): string { return 'Beszel'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['systems', 'up', 'down']; }

    public function getFieldLabels(): array {
        return ['systems' => 'Systems', 'up' => 'Up', 'down' => 'Down'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $config['api_key'];
        }
        return [
            ['url' => $base . '/api/collections/systems/records', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $items = $responses[0]['items'] ?? [];
        if (!is_array($items)) $items = [];
        $up = 0; $down = 0;
        foreach ($items as $item) {
            if (($item['status'] ?? '') === 'up') $up++;
            else $down++;
        }
        return [
            'systems' => (string)count($items),
            'up' => (string)$up,
            'down' => (string)$down,
        ];
    }
}
