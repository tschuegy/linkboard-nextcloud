<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class FireflyWidget extends AbstractWidget {

    public function getId(): string { return 'firefly'; }
    public function getLabel(): string { return 'Firefly III'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Personal Access Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['netWorth', 'billsPaid', 'billsUnpaid']; }

    public function getFieldLabels(): array {
        return ['netWorth' => 'Net Worth', 'billsPaid' => 'Bills Paid', 'billsUnpaid' => 'Bills Unpaid'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/summary/basic?start=' . date('Y-01-01') . '&end=' . date('Y-12-31'), 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $netWorth = '0';
        foreach ($data as $key => $val) {
            if (str_starts_with($key, 'net-worth-in-')) {
                $netWorth = '$' . number_format((float)($val['value_parsed'] ?? $val['monetary_value'] ?? 0), 2);
                break;
            }
        }
        $billsPaid = $data['bills-paid-in-default-currency']['monetary_value'] ?? $data['bills-paid'] ?? 0;
        $billsUnpaid = $data['bills-unpaid-in-default-currency']['monetary_value'] ?? $data['bills-unpaid'] ?? 0;
        return [
            'netWorth' => $netWorth,
            'billsPaid' => '$' . number_format((float)$billsPaid, 2),
            'billsUnpaid' => '$' . number_format((float)$billsUnpaid, 2),
        ];
    }
}
