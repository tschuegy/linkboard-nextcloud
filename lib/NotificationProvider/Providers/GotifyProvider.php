<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class GotifyProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'gotify'; }
    public function getLabel(): string { return 'Gotify'; }
    public function getCategory(): string { return 'push'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'server_url', 'label' => 'Server URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://gotify.example.com'],
            ['key' => 'app_token', 'label' => 'App token', 'type' => 'password', 'required' => true],
            ['key' => 'priority', 'label' => 'Priority', 'type' => 'text', 'required' => false, 'placeholder' => '5'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $url = rtrim($config['server_url'], '/') . '/message?token=' . urlencode($config['app_token']);
        $result = $this->jsonPost($url, [
            'title' => $title,
            'message' => $message,
            'priority' => (int)($config['priority'] ?? 5),
        ]);
        $this->assertSuccess($result);
    }
}
