<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class ThreemaProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'threema'; }
    public function getLabel(): string { return 'Threema'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_id', 'label' => 'API ID', 'type' => 'text', 'required' => true, 'placeholder' => '*MYAPID'],
            ['key' => 'api_secret', 'label' => 'API secret', 'type' => 'password', 'required' => true],
            ['key' => 'recipient', 'label' => 'Recipient ID', 'type' => 'text', 'required' => true, 'placeholder' => 'ABCD1234'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $url = 'https://msgapi.threema.ch/send_simple';
        $result = $this->curlRequest($url, 'POST', [
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query([
            'from' => $config['api_id'],
            'secret' => $config['api_secret'],
            'to' => $config['recipient'],
            'text' => "{$title}\n{$message}",
        ]));
        $this->assertSuccess($result);
    }
}
