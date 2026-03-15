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

    public function getAllowedFields(): array { return ['version', 'db_version', 'notes']; }

    public function getFieldLabels(): array {
        return [
            'version' => 'Version',
            'db_version' => 'DB Version',
            'notes' => 'Notes',
        ];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/etapi/app-info', 'headers' => $headers],
            ['url' => $base . '/etapi/notes?search=*', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $version = $responses[0]['appVersion'] ?? '';
        $dbVersion = (string)($responses[0]['dbVersion'] ?? '');

        $notes = is_array($responses[1]['results'] ?? null) ? count($responses[1]['results']) : 0;

        return [
            'version' => $version,
            'db_version' => $dbVersion,
            'notes' => (string)$notes,
        ];
    }
}
