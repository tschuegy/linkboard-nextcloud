<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class GameDigWidget extends AbstractWidget {

    public function getId(): string { return 'gamedig'; }
    public function getLabel(): string { return 'GameDig'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['name', 'map', 'players', 'maxPlayers']; }

    public function getFieldLabels(): array {
        return ['name' => 'Server', 'map' => 'Map', 'players' => 'Players', 'maxPlayers' => 'Max'];
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
            'name' => (string)($data['name'] ?? '—'),
            'map' => (string)($data['map'] ?? '—'),
            'players' => (string)($data['players'] ?? 0),
            'maxPlayers' => (string)($data['maxplayers'] ?? 0),
        ];
    }
}
