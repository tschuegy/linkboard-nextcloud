<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class CoinMarketCapWidget extends AbstractWidget {

    public function getId(): string { return 'coinmarketcap'; }
    public function getLabel(): string { return 'CoinMarketCap'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'symbol', 'label' => 'Symbol', 'type' => 'text', 'required' => true, 'placeholder' => 'BTC'],
        ];
    }

    public function getAllowedFields(): array { return ['price', 'change1h', 'change24h']; }

    public function getFieldLabels(): array {
        return ['price' => 'Price', 'change1h' => '1h Change', 'change24h' => '24h Change'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $symbol = $config['symbol'] ?? 'BTC';
        $headers = ['X-CMC_PRO_API_KEY: ' . ($config['api_key'] ?? ''), 'Accept: application/json'];
        return [
            ['url' => 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest?symbol=' . $symbol, 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $symbol = strtoupper($config['symbol'] ?? 'BTC');
        $quote = $responses[0]['data'][$symbol]['quote']['USD'] ?? [];
        $price = round((float)($quote['price'] ?? 0), 2);
        $c1h = round((float)($quote['percent_change_1h'] ?? 0), 2);
        $c24h = round((float)($quote['percent_change_24h'] ?? 0), 2);
        return [
            'price' => '$' . number_format($price, 2),
            'change1h' => $c1h . '%',
            'change24h' => $c24h . '%',
        ];
    }
}
