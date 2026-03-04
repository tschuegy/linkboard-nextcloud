<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class KavitaWidget extends AbstractWidget {

    public function getId(): string { return 'kavita'; }
    public function getLabel(): string { return 'Kavita'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['series', 'volumes', 'chapters']; }

    public function getFieldLabels(): array {
        return ['series' => 'Series', 'volumes' => 'Volumes', 'chapters' => 'Chapters'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/api/Account/login',
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_kavita_login' => true,
            ],
            ['url' => $base . '/api/Server/stats', 'headers' => [], '_kavita_needs_token' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats = $responses[1] ?? [];
        return [
            'series' => (string)($stats['seriesCount'] ?? 0),
            'volumes' => (string)($stats['volumeCount'] ?? 0),
            'chapters' => (string)($stats['chapterCount'] ?? 0),
        ];
    }
}
