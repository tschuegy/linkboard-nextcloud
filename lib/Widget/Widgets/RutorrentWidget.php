<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class RutorrentWidget extends AbstractWidget {

    public function getId(): string { return 'rutorrent'; }
    public function getLabel(): string { return 'ruTorrent'; }

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
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth, 'Content-Type: application/json'];
        return [
            [
                'url' => $base . '/plugins/httprpc/action.php',
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode(['mode' => 'list']),
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $torrents = $data['t'] ?? [];
        $leech = 0; $seed = 0; $dlRate = 0; $ulRate = 0;
        if (is_array($torrents)) {
            foreach ($torrents as $t) {
                if (is_array($t)) {
                    $state = (int)($t[4] ?? 0);
                    if ($state === 1) $leech++;
                    else $seed++;
                    $dlRate += (float)($t[12] ?? 0);
                    $ulRate += (float)($t[11] ?? 0);
                }
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
