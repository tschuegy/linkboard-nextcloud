<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class QnapWidget extends AbstractWidget {

    public function getId(): string { return 'qnap'; }
    public function getLabel(): string { return 'QNAP'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => 'admin'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['cpuUsage', 'memUsage', 'volumes', 'drives']; }

    public function getFieldLabels(): array {
        return ['cpuUsage' => 'CPU', 'memUsage' => 'Memory', 'volumes' => 'Volumes', 'drives' => 'Drives'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            [
                'url' => $base . '/cgi-bin/authLogin.cgi',
                'method' => 'POST',
                'headers' => ['Content-Type: application/x-www-form-urlencoded'],
                'body' => http_build_query(['user' => $config['username'] ?? '', 'pwd' => base64_encode($config['password'] ?? '')]),
                '_session_login' => true,
            ],
            ['url' => $base . '/cgi-bin/management/manaRequest.cgi?subfunc=sysinfo&sysHealth=1', 'headers' => [], '_session_needs_cookie' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[1] ?? [];
        return [
            'cpuUsage' => (string)($data['cpu_usage'] ?? '0') . '%',
            'memUsage' => (string)($data['mem_usage'] ?? '0') . '%',
            'volumes' => (string)($data['vol_count'] ?? 0),
            'drives' => (string)($data['disk_count'] ?? 0),
        ];
    }
}
