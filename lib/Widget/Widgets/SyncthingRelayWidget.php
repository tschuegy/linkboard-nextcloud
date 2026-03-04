<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class SyncthingRelayWidget extends AbstractWidget {

    public function getId(): string { return 'syncthingrelay'; }
    public function getLabel(): string { return 'Syncthing Relay'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['activeSessions', 'connections', 'rate']; }

    public function getFieldLabels(): array {
        return ['activeSessions' => 'Sessions', 'connections' => 'Connections', 'rate' => 'Rate'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/status'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $rate = round((float)($data['kbps10s1m5m15m30m60m'] ?? [0])[0] ?? 0, 1);
        return [
            'activeSessions' => (string)($data['numActiveSessions'] ?? 0),
            'connections' => (string)($data['numConnections'] ?? 0),
            'rate' => $rate . ' KB/s',
        ];
    }
}
