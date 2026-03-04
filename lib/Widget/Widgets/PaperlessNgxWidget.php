<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class PaperlessNgxWidget extends AbstractWidget {

    public function getId(): string { return 'paperlessngx'; }
    public function getLabel(): string { return 'Paperless-ngx'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => false, 'placeholder' => 'Token or username:password'],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'placeholder' => 'If not using token'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => false, 'placeholder' => 'If not using token'],
        ];
    }

    public function getAllowedFields(): array { return ['total', 'inbox']; }

    public function getFieldLabels(): array {
        return ['total' => 'Total', 'inbox' => 'Inbox'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = [];
        if (!empty($config['api_key'])) {
            $headers[] = 'Authorization: Token ' . $config['api_key'];
        } elseif (!empty($config['username'])) {
            $headers[] = 'Authorization: Basic ' . base64_encode($config['username'] . ':' . ($config['password'] ?? ''));
        }
        return [
            ['url' => $base . '/api/documents/?page=1&page_size=1', 'headers' => $headers],
            ['url' => $base . '/api/documents/?page=1&page_size=1&tags__name__iexact=inbox', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        return [
            'total' => (string)($responses[0]['count'] ?? 0),
            'inbox' => (string)($responses[1]['count'] ?? 0),
        ];
    }
}
