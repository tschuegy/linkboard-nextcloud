<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class DiscordProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'discord'; }
    public function getLabel(): string { return 'Discord'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://discord.com/api/webhooks/...'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $result = $this->jsonPost($config['webhook_url'], [
            'content' => "**{$title}**\n{$message}",
        ]);
        $this->assertSuccess($result, 204);
    }
}
