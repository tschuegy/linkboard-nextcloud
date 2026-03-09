<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class GoogleChatProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'google_chat'; }
    public function getLabel(): string { return 'Google Chat'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://chat.googleapis.com/v1/spaces/...'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $result = $this->jsonPost($config['webhook_url'], [
            'text' => "*{$title}*\n{$message}",
        ]);
        $this->assertSuccess($result);
    }
}
