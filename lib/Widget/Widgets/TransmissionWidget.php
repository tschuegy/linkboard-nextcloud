<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class TransmissionWidget extends AbstractWidget {

    public function getId(): string { return 'transmission'; }
    public function getLabel(): string { return 'Transmission'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'placeholder' => 'Optional'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['leech', 'download', 'seed', 'upload']; }

    public function getFieldLabels(): array {
        return ['leech' => 'Leech', 'download' => 'Download', 'seed' => 'Seed', 'upload' => 'Upload'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Content-Type: application/json'];
        if (!empty($config['username'])) {
            $headers[] = 'Authorization: Basic ' . base64_encode($config['username'] . ':' . ($config['password'] ?? ''));
        }
        return [
            [
                'url' => $base . '/transmission/rpc',
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode(['method' => 'torrent-get', 'arguments' => ['fields' => ['status', 'rateDownload', 'rateUpload']]]),
                '_transmission_rpc' => true,
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $torrents = $responses[0]['arguments']['torrents'] ?? [];
        $leech = 0; $seed = 0; $dlRate = 0; $ulRate = 0;
        if (is_array($torrents)) {
            foreach ($torrents as $t) {
                $status = $t['status'] ?? 0;
                if ($status === 4) $leech++;
                elseif ($status === 6) $seed++;
                $dlRate += (float)($t['rateDownload'] ?? 0);
                $ulRate += (float)($t['rateUpload'] ?? 0);
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
