<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class OctoPrintWidget extends AbstractWidget {

    public function getId(): string { return 'octoprint'; }
    public function getLabel(): string { return 'OctoPrint'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['printerState', 'progress', 'bedTemp', 'toolTemp']; }

    public function getFieldLabels(): array {
        return ['printerState' => 'State', 'progress' => 'Progress', 'bedTemp' => 'Bed', 'toolTemp' => 'Tool'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Api-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/job', 'headers' => $headers],
            ['url' => $base . '/api/printer', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $job = $responses[0] ?? [];
        $printer = $responses[1] ?? [];
        $state = $job['state'] ?? 'unknown';
        $progress = round((float)($job['progress']['completion'] ?? 0));
        $bedTemp = round((float)($printer['temperature']['bed']['actual'] ?? 0));
        $toolTemp = round((float)($printer['temperature']['tool0']['actual'] ?? 0));
        return [
            'printerState' => (string)$state,
            'progress' => $progress . '%',
            'bedTemp' => $bedTemp . '°C',
            'toolTemp' => $toolTemp . '°C',
        ];
    }
}
