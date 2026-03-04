<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class MinecraftWidget extends AbstractWidget {

    public function getId(): string { return 'minecraft'; }
    public function getLabel(): string { return 'Minecraft'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['players', 'maxPlayers', 'version']; }

    public function getFieldLabels(): array {
        return ['players' => 'Players', 'maxPlayers' => 'Max', 'version' => 'Version'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        // Uses a status proxy or direct query endpoint
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/status'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $players = $data['players']['online'] ?? $data['players'] ?? 0;
        $max = $data['players']['max'] ?? $data['maxPlayers'] ?? 0;
        $version = $data['version']['name'] ?? $data['version'] ?? '—';
        return [
            'players' => (string)$players,
            'maxPlayers' => (string)$max,
            'version' => (string)$version,
        ];
    }
}
