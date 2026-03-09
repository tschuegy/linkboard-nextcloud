<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class CallMeBotProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'callmebot'; }
    public function getLabel(): string { return 'CallMeBot'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'phone_number', 'label' => 'Phone number', 'type' => 'text', 'required' => true, 'placeholder' => '+49123456789'],
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'required' => true],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $text = urlencode($title . "\n" . $message);
        $url = 'https://api.callmebot.com/whatsapp.php?phone=' . urlencode($config['phone_number'])
            . '&apikey=' . urlencode($config['api_key'])
            . '&text=' . $text;

        $result = $this->curlRequest($url, 'GET', [], null);
        $this->assertSuccess($result);
    }
}
