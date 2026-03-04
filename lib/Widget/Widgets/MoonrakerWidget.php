<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class MoonrakerWidget extends AbstractWidget {

    public function getId(): string { return 'moonraker'; }
    public function getLabel(): string { return 'Moonraker'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
        ];
    }

    public function getAllowedFields(): array { return ['printerState', 'progress', 'extruderTemp', 'bedTemp']; }

    public function getFieldLabels(): array {
        return ['printerState' => 'State', 'progress' => 'Progress', 'extruderTemp' => 'Extruder', 'bedTemp' => 'Bed'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['api_key'])) {
            $headers[] = 'X-Api-Key: ' . $config['api_key'];
        }
        return [
            ['url' => $base . '/printer/info', 'headers' => $headers],
            ['url' => $base . '/printer/objects/query?display_status&extruder&heater_bed', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $info = $responses[0]['result'] ?? [];
        $objects = $responses[1]['result']['status'] ?? [];
        $progress = round((float)($objects['display_status']['progress'] ?? 0) * 100);
        $extruder = round((float)($objects['extruder']['temperature'] ?? 0));
        $bed = round((float)($objects['heater_bed']['temperature'] ?? 0));
        return [
            'printerState' => (string)($info['state'] ?? 'unknown'),
            'progress' => $progress . '%',
            'extruderTemp' => $extruder . '°C',
            'bedTemp' => $bed . '°C',
        ];
    }
}
