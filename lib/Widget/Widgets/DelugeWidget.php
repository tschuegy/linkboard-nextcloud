<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class DelugeWidget extends AbstractWidget {

    public function getId(): string { return 'deluge'; }
    public function getLabel(): string { return 'Deluge'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['leech', 'download', 'seed', 'upload']; }

    public function getFieldLabels(): array {
        return ['leech' => 'Leech', 'download' => 'Download', 'seed' => 'Seed', 'upload' => 'Upload'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Content-Type: application/json'];
        return [
            [
                'url' => $base . '/json',
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode(['method' => 'auth.login', 'params' => [$config['password'] ?? ''], 'id' => 1]),
                '_session_login' => true,
            ],
            [
                'url' => $base . '/json',
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode(['method' => 'web.update_ui', 'params' => [[''], []], 'id' => 2]),
                '_session_needs_cookie' => true,
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[1]['result'] ?? [];
        $stats = $data['stats'] ?? [];
        $torrents = $data['torrents'] ?? [];
        $leech = 0; $seed = 0;
        if (is_array($torrents)) {
            foreach ($torrents as $t) {
                $state = $t['state'] ?? '';
                if ($state === 'Downloading') $leech++;
                elseif ($state === 'Seeding') $seed++;
            }
        }
        $dl = round((float)($stats['download_rate'] ?? 0) / 1024, 1);
        $ul = round((float)($stats['upload_rate'] ?? 0) / 1024, 1);
        return [
            'leech' => (string)$leech,
            'download' => $dl . ' KB/s',
            'seed' => (string)$seed,
            'upload' => $ul . ' KB/s',
        ];
    }
}
