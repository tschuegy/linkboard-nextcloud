<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class RommWidget extends AbstractWidget {

    public function getId(): string { return 'romm'; }
    public function getLabel(): string { return 'ROMM'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['platforms', 'roms']; }

    public function getFieldLabels(): array {
        return ['platforms' => 'Platforms', 'roms' => 'ROMs'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/api/platforms', 'headers' => $headers],
            ['url' => $base . '/api/roms?limit=1', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $r0 = $responses[0] ?? [];
        $r1 = $responses[1] ?? [];
        $platforms = is_array($r0) ? count($r0) : 0;
        $roms = $r1['total'] ?? (is_array($r1) ? count($r1) : 0);
        return [
            'platforms' => (string)$platforms,
            'roms' => (string)$roms,
        ];
    }
}
