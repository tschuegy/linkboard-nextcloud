<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class AutobrrWidget extends AbstractWidget {

    public function getId(): string { return 'autobrr'; }
    public function getLabel(): string { return 'Autobrr'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['filters', 'pushApproved', 'pushRejected']; }

    public function getFieldLabels(): array {
        return ['filters' => 'Filters', 'pushApproved' => 'Approved', 'pushRejected' => 'Rejected'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Api-Token: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/filters', 'headers' => $headers],
            ['url' => $base . '/api/release/stats', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $filters = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $stats = $responses[1] ?? [];
        return [
            'filters' => (string)$filters,
            'pushApproved' => (string)($stats['push_approved_count'] ?? 0),
            'pushRejected' => (string)($stats['push_rejected_count'] ?? 0),
        ];
    }
}
