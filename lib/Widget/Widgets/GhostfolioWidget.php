<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class GhostfolioWidget extends AbstractWidget {

    public function getId(): string { return 'ghostfolio'; }
    public function getLabel(): string { return 'Ghostfolio'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Security Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['grossPerformance', 'netWorth']; }

    public function getFieldLabels(): array {
        return ['grossPerformance' => 'Performance', 'netWorth' => 'Net Worth'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/portfolio/performance?range=max', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $perf = $data['performance'] ?? [];
        return [
            'grossPerformance' => '$' . number_format((float)($perf['currentGrossPerformance'] ?? 0), 2),
            'netWorth' => '$' . number_format((float)($perf['currentNetWorth'] ?? $perf['currentValue'] ?? 0), 2),
        ];
    }
}
