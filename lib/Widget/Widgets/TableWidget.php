<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TableWidget extends AbstractWidget {

    public function getId(): string { return 'table'; }
    public function getLabel(): string { return 'Table'; }

    public function isLocal(): bool { return true; }

    public function getConfigFields(): array { return []; }
    public function getAllowedFields(): array { return []; }
    public function getFieldLabels(): array { return []; }

    public function buildRequests(string $baseUrl, array $config): array {
        return [];
    }

    public function mapResponse(array $responses, array $config): array {
        return [
            '_tableData' => json_encode([
                'columns' => $config['columns'] ?? [],
                'rows' => $config['rows'] ?? [],
            ]),
        ];
    }
}
