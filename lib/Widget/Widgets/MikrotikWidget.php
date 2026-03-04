<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class MikrotikWidget extends AbstractWidget {

    public function getId(): string { return 'mikrotik'; }
    public function getLabel(): string { return 'Mikrotik'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => 'admin'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['cpuLoad', 'memUsed', 'uptime']; }

    public function getFieldLabels(): array {
        return ['cpuLoad' => 'CPU', 'memUsed' => 'Memory', 'uptime' => 'Uptime'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/rest/system/resource', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $cpuLoad = (int)($data['cpu-load'] ?? 0);
        $totalMem = (int)($data['total-memory'] ?? 1);
        $freeMem = (int)($data['free-memory'] ?? 0);
        $memPct = round(($totalMem - $freeMem) / $totalMem * 100);
        return [
            'cpuLoad' => $cpuLoad . '%',
            'memUsed' => $memPct . '%',
            'uptime' => (string)($data['uptime'] ?? '0s'),
        ];
    }
}
