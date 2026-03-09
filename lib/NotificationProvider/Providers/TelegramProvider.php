<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class TelegramProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'telegram'; }
    public function getLabel(): string { return 'Telegram'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'bot_token', 'label' => 'Bot token', 'type' => 'password', 'required' => true],
            ['key' => 'chat_id', 'label' => 'Chat ID', 'type' => 'text', 'required' => true, 'placeholder' => '-1001234567890'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $url = 'https://api.telegram.org/bot' . $config['bot_token'] . '/sendMessage';
        $result = $this->jsonPost($url, [
            'chat_id' => $config['chat_id'],
            'text' => "<b>{$title}</b>\n{$message}",
            'parse_mode' => 'HTML',
        ]);
        $this->assertSuccess($result);
    }
}
