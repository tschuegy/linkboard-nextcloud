<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class FileflowsWidget extends AbstractWidget {

    public function getId(): string { return 'fileflows'; }
    public function getLabel(): string { return 'FileFlows'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['processed', 'processing', 'unprocessed']; }

    public function getFieldLabels(): array {
        return ['processed' => 'Processed', 'processing' => 'Processing', 'unprocessed' => 'Unprocessed'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/status'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'processed' => (string)($data['Processed'] ?? 0),
            'processing' => (string)($data['Processing'] ?? 0),
            'unprocessed' => (string)($data['Unprocessed'] ?? 0),
        ];
    }
}
