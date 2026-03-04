<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class GotifyWidget extends AbstractWidget {

    public function getId(): string { return 'gotify'; }
    public function getLabel(): string { return 'Gotify'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Client Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['apps', 'clients', 'messages']; }

    public function getFieldLabels(): array {
        return ['apps' => 'Apps', 'clients' => 'Clients', 'messages' => 'Messages'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-Gotify-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/application', 'headers' => $headers],
            ['url' => $base . '/client', 'headers' => $headers],
            ['url' => $base . '/message?limit=0', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $apps = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $clients = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $messages = $responses[2]['paging']['size'] ?? (is_array($responses[2]['messages'] ?? null) ? count($responses[2]['messages']) : 0);
        return [
            'apps' => (string)$apps,
            'clients' => (string)$clients,
            'messages' => (string)$messages,
        ];
    }
}
