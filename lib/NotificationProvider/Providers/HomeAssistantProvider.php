<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class HomeAssistantProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'home_assistant'; }
    public function getLabel(): string { return 'Home Assistant'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'url', 'label' => 'URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://homeassistant.local:8123'],
            ['key' => 'long_lived_access_token', 'label' => 'Long-lived access token', 'type' => 'password', 'required' => true],
            ['key' => 'notification_service', 'label' => 'Notification service', 'type' => 'text', 'required' => false, 'placeholder' => 'notify.notify'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $service = $config['notification_service'] ?? 'notify.notify';
        $url = rtrim($config['url'], '/') . '/api/services/' . str_replace('.', '/', $service);
        $result = $this->jsonPost($url, [
            'title' => $title,
            'message' => $message,
        ], [
            'Authorization: Bearer ' . $config['long_lived_access_token'],
        ]);
        $this->assertSuccess($result);
    }
}
