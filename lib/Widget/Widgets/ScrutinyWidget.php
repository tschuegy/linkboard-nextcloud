<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ScrutinyWidget extends AbstractWidget {

    public function getId(): string { return 'scrutiny'; }
    public function getLabel(): string { return 'Scrutiny'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['passed', 'failed', 'unknown']; }

    public function getFieldLabels(): array {
        return ['passed' => 'Passed', 'failed' => 'Failed', 'unknown' => 'Unknown'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/summary'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0]['data']['summary'] ?? [];
        $passed = 0; $failed = 0; $unknown = 0;
        if (is_array($data)) {
            foreach ($data as $device) {
                $status = $device['device']['device_status'] ?? -1;
                if ($status === 0) $passed++;
                elseif ($status > 0) $failed++;
                else $unknown++;
            }
        }
        return [
            'passed' => (string)$passed,
            'failed' => (string)$failed,
            'unknown' => (string)$unknown,
        ];
    }
}
