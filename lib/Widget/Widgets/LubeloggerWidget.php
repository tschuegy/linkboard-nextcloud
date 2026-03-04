<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class LubeloggerWidget extends AbstractWidget {

    public function getId(): string { return 'lubelogger'; }
    public function getLabel(): string { return 'LubeLogger'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['vehicles', 'reminders', 'records']; }

    public function getFieldLabels(): array {
        return ['vehicles' => 'Vehicles', 'reminders' => 'Reminders', 'records' => 'Records'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        return [
            ['url' => $base . '/api/vehicles', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $vehicles = $responses[0] ?? [];
        if (!is_array($vehicles)) $vehicles = [];
        return [
            'vehicles' => (string)count($vehicles),
            'reminders' => '0',
            'records' => '0',
        ];
    }
}
