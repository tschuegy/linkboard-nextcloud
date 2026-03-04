<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class DownloadStationWidget extends AbstractWidget {

    public function getId(): string { return 'downloadstation'; }
    public function getLabel(): string { return 'Download Station'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['active', 'completed', 'total']; }

    public function getFieldLabels(): array {
        return ['active' => 'Active', 'completed' => 'Completed', 'total' => 'Total'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $user = urlencode($config['username'] ?? '');
        $pass = urlencode($config['password'] ?? '');
        return [
            [
                'url' => $base . '/webapi/auth.cgi?api=SYNO.API.Auth&version=6&method=login&account=' . $user . '&passwd=' . $pass . '&session=DownloadStation&format=sid',
                '_session_login' => true,
            ],
            [
                'url' => $base . '/webapi/DownloadStation/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=list',
                'headers' => [],
                '_session_needs_cookie' => true,
            ],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $tasks = $responses[1]['data']['tasks'] ?? [];
        if (!is_array($tasks)) $tasks = [];
        $active = 0; $completed = 0;
        foreach ($tasks as $t) {
            $status = $t['status'] ?? '';
            if ($status === 'finished') $completed++;
            elseif (in_array($status, ['downloading', 'seeding'])) $active++;
        }
        return [
            'active' => (string)$active,
            'completed' => (string)$completed,
            'total' => (string)count($tasks),
        ];
    }
}
