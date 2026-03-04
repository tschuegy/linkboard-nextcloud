<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class XteveWidget extends AbstractWidget {

    public function getId(): string { return 'xteve'; }
    public function getLabel(): string { return 'xTeVe'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['streams', 'channels']; }

    public function getFieldLabels(): array {
        return ['streams' => 'Streams', 'channels' => 'Channels'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'streams' => (string)($data['streams'] ?? 0),
            'channels' => (string)($data['channels'] ?? 0),
        ];
    }
}
