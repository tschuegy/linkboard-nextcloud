<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class WallosWidget extends AbstractWidget {

    public function getId(): string { return 'wallos'; }
    public function getLabel(): string { return 'Wallos'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['monthlyCost', 'yearlyCost', 'subscriptions']; }

    public function getFieldLabels(): array {
        return ['monthlyCost' => 'Monthly', 'yearlyCost' => 'Yearly', 'subscriptions' => 'Subscriptions'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/api/subscriptions'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $subs = $responses[0] ?? [];
        if (!is_array($subs)) $subs = [];
        $monthly = 0; $yearly = 0;
        foreach ($subs as $s) {
            $price = (float)($s['price'] ?? 0);
            $cycle = $s['cycle'] ?? 'monthly';
            if ($cycle === 'monthly') { $monthly += $price; $yearly += $price * 12; }
            elseif ($cycle === 'yearly') { $yearly += $price; $monthly += $price / 12; }
        }
        return [
            'monthlyCost' => '$' . number_format($monthly, 2),
            'yearlyCost' => '$' . number_format($yearly, 2),
            'subscriptions' => (string)count($subs),
        ];
    }
}
