<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class StocksWidget extends AbstractWidget {

    public function getId(): string { return 'stocks'; }
    public function getLabel(): string { return 'Stocks'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Finnhub API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'symbol', 'label' => 'Symbol', 'type' => 'text', 'required' => true, 'placeholder' => 'AAPL'],
        ];
    }

    public function getAllowedFields(): array { return ['price', 'change', 'changePercent']; }

    public function getFieldLabels(): array {
        return ['price' => 'Price', 'change' => 'Change', 'changePercent' => 'Change %'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $symbol = $config['symbol'] ?? 'AAPL';
        $key = $config['api_key'] ?? '';
        return [
            ['url' => 'https://finnhub.io/api/v1/quote?symbol=' . $symbol . '&token=' . $key],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $price = round((float)($data['c'] ?? 0), 2);
        $change = round((float)($data['d'] ?? 0), 2);
        $changePct = round((float)($data['dp'] ?? 0), 2);
        return [
            'price' => '$' . number_format($price, 2),
            'change' => ($change >= 0 ? '+' : '') . $change,
            'changePercent' => ($changePct >= 0 ? '+' : '') . $changePct . '%',
        ];
    }
}
