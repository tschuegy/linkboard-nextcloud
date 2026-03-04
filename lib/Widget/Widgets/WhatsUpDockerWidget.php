<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class WhatsUpDockerWidget extends AbstractWidget {

    public function getId(): string { return 'whatsupdocker'; }
    public function getLabel(): string { return 'What\'s Up Docker'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['monitored', 'updates', 'upToDate']; }

    public function getFieldLabels(): array {
        return ['monitored' => 'Monitored', 'updates' => 'Updates', 'upToDate' => 'Up to Date'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/containers'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $containers = $responses[0] ?? [];
        if (!is_array($containers)) $containers = [];
        $updates = 0; $upToDate = 0;
        foreach ($containers as $c) {
            if ($c['updateAvailable'] ?? false) $updates++;
            else $upToDate++;
        }
        return [
            'monitored' => (string)count($containers),
            'updates' => (string)$updates,
            'upToDate' => (string)$upToDate,
        ];
    }
}
