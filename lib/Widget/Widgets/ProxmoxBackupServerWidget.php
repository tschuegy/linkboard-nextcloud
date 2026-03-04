<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ProxmoxBackupServerWidget extends AbstractWidget {

    public function getId(): string { return 'proxmoxbackupserver'; }
    public function getLabel(): string { return 'Proxmox Backup Server'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_token', 'label' => 'API Token (user@realm!tokenid=secret)', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['datastoreUsage', 'failedTasks', 'cpu', 'memory']; }

    public function getFieldLabels(): array {
        return ['datastoreUsage' => 'Datastore', 'failedTasks' => 'Failed', 'cpu' => 'CPU', 'memory' => 'Memory'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: PBSAPIToken=' . ($config['api_token'] ?? '')];
        return [
            ['url' => $base . '/api2/json/status/datastore-usage', 'headers' => $headers],
            ['url' => $base . '/api2/json/nodes/localhost/status', 'headers' => $headers],
            ['url' => $base . '/api2/json/nodes/localhost/tasks?limit=50&typefilter=garbage_collection&statusfilter=error', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $stores = $responses[0]['data'] ?? [];
        $usagePct = '0%';
        if (is_array($stores) && count($stores) > 0) {
            $total = (float)($stores[0]['total'] ?? 1);
            $used = (float)($stores[0]['used'] ?? 0);
            $usagePct = round($used / $total * 100) . '%';
        }
        $node = $responses[1]['data'] ?? [];
        $cpu = round((float)($node['cpu'] ?? 0) * 100);
        $memTotal = (float)($node['memory']['total'] ?? 1);
        $memUsed = (float)($node['memory']['used'] ?? 0);
        $mem = round($memUsed / $memTotal * 100);
        $failed = is_array($responses[2]['data'] ?? null) ? count($responses[2]['data']) : 0;
        return [
            'datastoreUsage' => $usagePct,
            'failedTasks' => (string)$failed,
            'cpu' => $cpu . '%',
            'memory' => $mem . '%',
        ];
    }
}
