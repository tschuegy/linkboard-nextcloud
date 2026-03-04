<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ProxmoxWidget extends AbstractWidget {

    public function getId(): string { return 'proxmox'; }
    public function getLabel(): string { return 'Proxmox VE'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_token', 'label' => 'API Token (PVEAPIToken)', 'type' => 'password', 'required' => true, 'placeholder' => 'user@pam!tokenid=UUID'],
        ];
    }

    public function getAllowedFields(): array { return ['vms', 'lxc', 'cpu', 'mem']; }

    public function getFieldLabels(): array {
        return ['vms' => 'VMs', 'lxc' => 'LXC', 'cpu' => 'CPU', 'mem' => 'Memory'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        return [[
            'url' => rtrim($baseUrl, '/') . '/api2/json/cluster/resources',
            'headers' => ['Authorization: PVEAPIToken=' . ($config['api_token'] ?? '')],
        ]];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0]['data'] ?? [];
        $vms = 0; $vmsRunning = 0;
        $lxc = 0; $lxcRunning = 0;
        $cpuSum = 0; $cpuCount = 0;
        $memUsed = 0; $memTotal = 0;

        foreach ($data as $item) {
            $type = $item['type'] ?? '';
            $status = $item['status'] ?? '';
            if ($type === 'qemu') {
                $vms++;
                if ($status === 'running') $vmsRunning++;
            } elseif ($type === 'lxc') {
                $lxc++;
                if ($status === 'running') $lxcRunning++;
            } elseif ($type === 'node') {
                $cpuSum += ($item['cpu'] ?? 0);
                $cpuCount++;
                $memUsed += ($item['mem'] ?? 0);
                $memTotal += ($item['maxmem'] ?? 0);
            }
        }

        return [
            'vms' => $vmsRunning . '/' . $vms,
            'lxc' => $lxcRunning . '/' . $lxc,
            'cpu' => $cpuCount > 0 ? round($cpuSum / $cpuCount * 100) . '%' : '0%',
            'mem' => $memTotal > 0 ? round($memUsed / $memTotal * 100) . '%' : '0%',
        ];
    }
}
