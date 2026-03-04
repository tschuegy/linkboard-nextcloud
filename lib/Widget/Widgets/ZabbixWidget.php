<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ZabbixWidget extends AbstractWidget {

    public function getId(): string { return 'zabbix'; }
    public function getLabel(): string { return 'Zabbix'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['hosts', 'problems', 'triggers']; }

    public function getFieldLabels(): array {
        return ['hosts' => 'Hosts', 'problems' => 'Problems', 'triggers' => 'Triggers'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Content-Type: application/json-rpc', 'Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api_jsonrpc.php', 'method' => 'POST', 'headers' => $headers,
                'body' => json_encode(['jsonrpc' => '2.0', 'method' => 'host.get', 'params' => ['countOutput' => true], 'id' => 1])],
            ['url' => $base . '/api_jsonrpc.php', 'method' => 'POST', 'headers' => $headers,
                'body' => json_encode(['jsonrpc' => '2.0', 'method' => 'problem.get', 'params' => ['countOutput' => true, 'recent' => true], 'id' => 2])],
            ['url' => $base . '/api_jsonrpc.php', 'method' => 'POST', 'headers' => $headers,
                'body' => json_encode(['jsonrpc' => '2.0', 'method' => 'trigger.get', 'params' => ['countOutput' => true, 'only_true' => true], 'id' => 3])],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        return [
            'hosts' => (string)($responses[0]['result'] ?? 0),
            'problems' => (string)($responses[1]['result'] ?? 0),
            'triggers' => (string)($responses[2]['result'] ?? 0),
        ];
    }
}
