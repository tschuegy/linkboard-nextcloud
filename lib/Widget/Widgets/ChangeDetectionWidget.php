<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ChangeDetectionWidget extends AbstractWidget {

    public function getId(): string { return 'changedetectionio'; }
    public function getLabel(): string { return 'ChangeDetection.io'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['watching', 'changed', 'unchanged']; }

    public function getFieldLabels(): array {
        return ['watching' => 'Watching', 'changed' => 'Changed', 'unchanged' => 'Unchanged'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['api_key'])) {
            $headers[] = 'x-api-key: ' . $config['api_key'];
        }
        return [
            ['url' => $base . '/api/v1/watch', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $watches = $responses[0] ?? [];
        if (!is_array($watches)) $watches = [];
        $total = count($watches);
        $changed = 0;
        foreach ($watches as $w) {
            if (is_array($w) && ($w['last_changed'] ?? 0) > 0) $changed++;
        }
        return [
            'watching' => (string)$total,
            'changed' => (string)$changed,
            'unchanged' => (string)($total - $changed),
        ];
    }
}
