<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TriliumWidget extends AbstractWidget {

    public function getId(): string { return 'trilium'; }
    public function getLabel(): string { return 'Trilium'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'ETAPI Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['notes']; }

    public function getFieldLabels(): array {
        return ['notes' => 'Notes'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/etapi/notes', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $notes = is_array($responses[0]['results'] ?? null) ? count($responses[0]['results']) : 0;
        return [
            'notes' => (string)$notes,
        ];
    }
}
