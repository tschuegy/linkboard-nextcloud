<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class JdownloaderWidget extends AbstractWidget {

    public function getId(): string { return 'jdownloader'; }
    public function getLabel(): string { return 'JDownloader'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'MyJD Email', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'MyJD Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'device', 'label' => 'Device Name', 'type' => 'text', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['speed', 'packages', 'links']; }

    public function getFieldLabels(): array {
        return ['speed' => 'Speed', 'packages' => 'Packages', 'links' => 'Links'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        // JDownloader uses the MyJDownloader API (my.jdownloader.org)
        // This requires a complex auth flow; basic stats endpoint
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/downloadcontroller/getCurrentState', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0]['data'] ?? $responses[0] ?? [];
        return [
            'speed' => round((float)($data['speed'] ?? 0) / 1024, 1) . ' KB/s',
            'packages' => (string)($data['packages'] ?? 0),
            'links' => (string)($data['links'] ?? 0),
        ];
    }
}
