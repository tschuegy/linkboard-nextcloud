<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class FloodWidget extends AbstractWidget {

    public function getId(): string { return 'flood'; }
    public function getLabel(): string { return 'Flood'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['leech', 'download', 'seed', 'upload']; }

    public function getFieldLabels(): array {
        return ['leech' => 'Leech', 'download' => 'Download', 'seed' => 'Seed', 'upload' => 'Upload'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/auth/authenticate',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_flood_login' => true,
            ],
            ['url' => $base . '/api/torrents', 'headers' => [], '_flood_needs_token' => true],
            ['url' => $base . '/api/client/connection-test', 'headers' => [], '_flood_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $torrents = $responses[1]['torrents'] ?? [];
        $leech = 0; $seed = 0; $dlRate = 0; $ulRate = 0;
        if (is_array($torrents)) {
            foreach ($torrents as $t) {
                $status = $t['status'] ?? [];
                if (is_array($status) && in_array('downloading', $status)) $leech++;
                if (is_array($status) && in_array('seeding', $status)) $seed++;
                $dlRate += (float)($t['downRate'] ?? 0);
                $ulRate += (float)($t['upRate'] ?? 0);
            }
        }
        return [
            'leech' => (string)$leech,
            'download' => round($dlRate / 1024, 1) . ' KB/s',
            'seed' => (string)$seed,
            'upload' => round($ulRate / 1024, 1) . ' KB/s',
        ];
    }
}
