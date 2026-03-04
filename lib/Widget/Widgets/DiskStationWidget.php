<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class DiskStationWidget extends AbstractWidget {

    public function getId(): string { return 'diskstation'; }
    public function getLabel(): string { return 'DiskStation (Synology)'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['cpuLoad', 'memUsage', 'volumes']; }

    public function getFieldLabels(): array {
        return ['cpuLoad' => 'CPU', 'memUsage' => 'Memory', 'volumes' => 'Volumes'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $user = urlencode($config['username'] ?? '');
        $pass = urlencode($config['password'] ?? '');
        return [
            [
                'url' => $base . '/webapi/auth.cgi?api=SYNO.API.Auth&version=6&method=login&account=' . $user . '&passwd=' . $pass . '&format=sid',
                '_session_login' => true,
            ],
            ['url' => $base . '/webapi/entry.cgi?api=SYNO.Core.System.Utilization&version=1&method=get', 'headers' => [], '_session_needs_cookie' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[1]['data'] ?? [];
        $cpu = $data['cpu']['user_load'] ?? 0;
        $mem = $data['memory']['real_usage'] ?? 0;
        $volumes = 0;
        if (isset($data['volume']) && is_array($data['volume'])) {
            $volumes = count($data['volume']);
        }
        return [
            'cpuLoad' => (string)$cpu . '%',
            'memUsage' => (string)$mem . '%',
            'volumes' => (string)$volumes,
        ];
    }
}
