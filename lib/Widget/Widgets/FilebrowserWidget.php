<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class FilebrowserWidget extends AbstractWidget {

    public function getId(): string { return 'filebrowser'; }
    public function getLabel(): string { return 'Filebrowser'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => 'admin'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['usage', 'files', 'dirs']; }

    public function getFieldLabels(): array {
        return ['usage' => 'Usage', 'files' => 'Files', 'dirs' => 'Dirs'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/login',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_filebrowser_login' => true,
            ],
            ['url' => $base . '/api/usage', 'headers' => [], '_filebrowser_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[1] ?? [];
        $usedBytes = (int)($data['used'] ?? 0);
        $gb = round($usedBytes / 1073741824, 1);
        return [
            'usage' => $gb . ' GB',
            'files' => (string)($data['files'] ?? 0),
            'dirs' => (string)($data['dirs'] ?? 0),
        ];
    }
}
