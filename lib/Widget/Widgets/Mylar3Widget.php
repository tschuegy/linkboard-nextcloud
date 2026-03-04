<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class Mylar3Widget extends AbstractWidget {

    public function getId(): string { return 'mylar3'; }
    public function getLabel(): string { return 'Mylar3'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['total', 'have', 'missing']; }

    public function getFieldLabels(): array {
        return ['total' => 'Total', 'have' => 'Have', 'missing' => 'Missing'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $key = $config['api_key'] ?? '';
        return [
            ['url' => $base . '/api?cmd=getComics&apikey=' . $key],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $comics = $responses[0]['data'] ?? [];
        if (!is_array($comics)) $comics = [];
        $total = count($comics);
        $have = 0;
        foreach ($comics as $c) {
            $have += (int)($c['haveIssues'] ?? 0);
        }
        $totalIssues = 0;
        foreach ($comics as $c) {
            $totalIssues += (int)($c['totalIssues'] ?? 0);
        }
        return [
            'total' => (string)$total,
            'have' => (string)$have,
            'missing' => (string)($totalIssues - $have),
        ];
    }
}
