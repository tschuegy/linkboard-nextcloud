<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class NetdataWidget extends AbstractWidget {

    public function getId(): string { return 'netdata'; }
    public function getLabel(): string { return 'Netdata'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['warnings', 'critical']; }

    public function getFieldLabels(): array {
        return ['warnings' => 'Warnings', 'critical' => 'Critical'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/v1/alarms?active'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $alarms = $responses[0]['alarms'] ?? [];
        $warnings = 0; $critical = 0;
        if (is_array($alarms)) {
            foreach ($alarms as $alarm) {
                $status = $alarm['status'] ?? '';
                if ($status === 'WARNING') $warnings++;
                elseif ($status === 'CRITICAL') $critical++;
            }
        }
        return [
            'warnings' => (string)$warnings,
            'critical' => (string)$critical,
        ];
    }
}
