<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class KopiaWidget extends AbstractWidget {

    public function getId(): string { return 'kopia'; }
    public function getLabel(): string { return 'Kopia'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'placeholder' => 'Optional'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['lastSnapshot', 'totalSize', 'status']; }

    public function getFieldLabels(): array {
        return ['lastSnapshot' => 'Last Snapshot', 'totalSize' => 'Size', 'status' => 'Status'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['username'])) {
            $headers[] = 'Authorization: Basic ' . base64_encode($config['username'] . ':' . ($config['password'] ?? ''));
        }
        return [
            ['url' => $base . '/api/v1/repo/status', 'headers' => $headers],
            ['url' => $base . '/api/v1/snapshots', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $status = $responses[0]['connected'] ?? false ? 'connected' : 'disconnected';
        $totalSize = (int)($responses[0]['storage']['totalSize'] ?? 0);
        $gb = round($totalSize / 1073741824, 1);
        $snapshots = $responses[1]['snapshots'] ?? [];
        $last = '—';
        if (is_array($snapshots) && count($snapshots) > 0) {
            $last = $snapshots[0]['startTime'] ?? '—';
            if (strlen($last) > 16) $last = substr($last, 0, 16);
        }
        return [
            'lastSnapshot' => $last,
            'totalSize' => $gb . ' GB',
            'status' => $status,
        ];
    }
}
