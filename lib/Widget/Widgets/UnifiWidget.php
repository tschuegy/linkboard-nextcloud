<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class UnifiWidget extends AbstractWidget {

    public function getId(): string { return 'unifi'; }
    public function getLabel(): string { return 'UniFi Controller'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'controllerType', 'label' => 'Controller Type', 'type' => 'select', 'required' => false, 'options' => ['UniFi OS (UDM, Cloud Key Gen2+)', 'Legacy Controller']],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'site', 'label' => 'Site', 'type' => 'text', 'required' => false, 'placeholder' => 'default'],
        ];
    }

    public function getAllowedFields(): array { return ['uptime', 'wanUsers', 'lanDevices', 'wlanUsers']; }

    public function getFieldLabels(): array {
        return ['uptime' => 'Uptime', 'wanUsers' => 'WAN', 'lanDevices' => 'LAN', 'wlanUsers' => 'WLAN'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $site = $config['site'] ?? 'default';
        $isLegacy = ($config['controllerType'] ?? '') === 'Legacy Controller';

        $loginPath = $isLegacy ? '/api/login' : '/api/auth/login';
        $healthPath = $isLegacy
            ? '/api/s/' . $site . '/stat/health'
            : '/proxy/network/api/s/' . $site . '/stat/health';

        return [
            [
                'url' => $base . $loginPath,
                'method' => 'POST',
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode(['username' => $config['username'] ?? '', 'password' => $config['password'] ?? '']),
                '_session_login' => true,
            ],
            ['url' => $base . $healthPath, 'headers' => [], '_session_needs_cookie' => true],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $health = $responses[1]['data'] ?? [];
        $wan = []; $lan = []; $wlan = [];
        if (is_array($health)) {
            foreach ($health as $sub) {
                $subsystem = $sub['subsystem'] ?? '';
                if ($subsystem === 'wan') $wan = $sub;
                elseif ($subsystem === 'lan') $lan = $sub;
                elseif ($subsystem === 'wlan') $wlan = $sub;
            }
        }
        return [
            'uptime' => (string)($wan['gw_system_stats']['uptime'] ?? '—'),
            'wanUsers' => (string)($wan['num_sta'] ?? 0),
            'lanDevices' => (string)($lan['num_sta'] ?? 0),
            'wlanUsers' => (string)($wlan['num_sta'] ?? 0),
        ];
    }
}
