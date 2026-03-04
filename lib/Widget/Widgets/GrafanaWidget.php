<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class GrafanaWidget extends AbstractWidget {

    public function getId(): string { return 'grafana'; }
    public function getLabel(): string { return 'Grafana'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['dashboards', 'datasources', 'alerts', 'alertsTriggered']; }

    public function getFieldLabels(): array {
        return ['dashboards' => 'Dashboards', 'datasources' => 'Data Sources', 'alerts' => 'Alerts', 'alertsTriggered' => 'Triggered'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/api/search?type=dash-db', 'headers' => $headers],
            ['url' => $base . '/api/datasources', 'headers' => $headers],
            ['url' => $base . '/api/v1/provisioning/alert-rules', 'headers' => $headers],
            ['url' => $base . '/api/alertmanager/grafana/api/v2/alerts', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $dashboards = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $datasources = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $alerts = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        $triggered = is_array($responses[3] ?? null) ? count($responses[3]) : 0;
        return [
            'dashboards' => (string)$dashboards,
            'datasources' => (string)$datasources,
            'alerts' => (string)$alerts,
            'alertsTriggered' => (string)$triggered,
        ];
    }
}
