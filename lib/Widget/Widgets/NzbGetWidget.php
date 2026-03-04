<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class NzbGetWidget extends AbstractWidget {

    public function getId(): string { return 'nzbget'; }
    public function getLabel(): string { return 'NZBGet'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['rate', 'remaining', 'downloaded']; }

    public function getFieldLabels(): array {
        return ['rate' => 'Speed', 'remaining' => 'Remaining', 'downloaded' => 'Downloaded'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth, 'Content-Type: application/json'];
        return [
            ['url' => $base . '/jsonrpc', 'method' => 'POST', 'headers' => $headers,
                'body' => json_encode(['method' => 'status', 'params' => []])],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $result = $responses[0]['result'] ?? [];
        $rate = (float)($result['DownloadRate'] ?? 0);
        $rateMb = round($rate / 1048576, 1);
        $remaining = round((float)($result['RemainingSizeMB'] ?? 0), 1);
        $downloaded = round((float)($result['DownloadedSizeMB'] ?? 0), 1);
        return [
            'rate' => $rateMb . ' MB/s',
            'remaining' => $remaining . ' MB',
            'downloaded' => $downloaded . ' MB',
        ];
    }
}
