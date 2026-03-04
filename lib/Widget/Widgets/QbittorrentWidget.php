<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class QbittorrentWidget extends AbstractWidget {

    public function getId(): string { return 'qbittorrent'; }
    public function getLabel(): string { return 'qBittorrent'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => 'admin'],
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
                'url' => $base . '/api/v2/auth/login',
                'method' => 'POST',
                'headers' => ['Content-Type: application/x-www-form-urlencoded'],
                'body' => http_build_query(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_session_login' => true,
            ],
            ['url' => $base . '/api/v2/transfer/info', 'headers' => [], '_session_needs_cookie' => true],
            ['url' => $base . '/api/v2/torrents/info', 'headers' => [], '_session_needs_cookie' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $transfer = $responses[1] ?? [];
        $torrents = $responses[2] ?? [];
        $dlSpeed = round((float)($transfer['dl_info_speed'] ?? 0) / 1024, 1);
        $ulSpeed = round((float)($transfer['up_info_speed'] ?? 0) / 1024, 1);
        $leech = 0; $seed = 0;
        if (is_array($torrents)) {
            foreach ($torrents as $t) {
                $state = $t['state'] ?? '';
                if (in_array($state, ['downloading', 'stalledDL', 'forcedDL', 'metaDL'])) $leech++;
                elseif (in_array($state, ['uploading', 'stalledUP', 'forcedUP'])) $seed++;
            }
        }
        return [
            'leech' => (string)$leech,
            'download' => $dlSpeed . ' KB/s',
            'seed' => (string)$seed,
            'upload' => $ulSpeed . ' KB/s',
        ];
    }
}
