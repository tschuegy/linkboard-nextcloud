<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class OpnSenseWidget extends AbstractWidget {

    public function getId(): string { return 'opnsense'; }
    public function getLabel(): string { return 'OPNsense'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'API Key', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'API Secret', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['cpuUsage', 'memUsage', 'activeInterfaces']; }

    public function getFieldLabels(): array {
        return ['cpuUsage' => 'CPU', 'memUsage' => 'Memory', 'activeInterfaces' => 'Interfaces'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/api/diagnostics/activity/getActivity', 'headers' => $headers],
            ['url' => $base . '/api/diagnostics/interface/getInterfaceStatistics', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $activity = $responses[0] ?? [];
        $cpuHeaders = $activity['headers'] ?? [];
        $cpuUsage = '0%';
        if (is_array($cpuHeaders) && isset($cpuHeaders[0])) {
            if (preg_match('/(\d+\.\d+)%/', $cpuHeaders[0], $m)) {
                $cpuUsage = $m[1] . '%';
            }
        }
        $memUsage = '0%';
        if (isset($cpuHeaders[1]) && preg_match('/(\d+)%/', $cpuHeaders[1], $m)) {
            $memUsage = $m[1] . '%';
        }
        $ifaces = $responses[1]['statistics'] ?? [];
        $activeIfaces = is_array($ifaces) ? count($ifaces) : 0;
        return [
            'cpuUsage' => $cpuUsage,
            'memUsage' => $memUsage,
            'activeInterfaces' => (string)$activeIfaces,
        ];
    }
}
