<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class SignalProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'signal'; }
    public function getLabel(): string { return 'Signal'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_url', 'label' => 'API URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://signal-api.example.com'],
            ['key' => 'number', 'label' => 'Sender number', 'type' => 'text', 'required' => true, 'placeholder' => '+49123456789'],
            ['key' => 'recipients', 'label' => 'Recipients (comma-separated)', 'type' => 'text', 'required' => true, 'placeholder' => '+49123456789,+49987654321'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $recipients = array_map('trim', explode(',', $config['recipients']));
        $url = rtrim($config['api_url'], '/') . '/v2/send';
        $result = $this->jsonPost($url, [
            'message' => "{$title}\n{$message}",
            'number' => $config['number'],
            'recipients' => $recipients,
        ]);
        $this->assertSuccess($result);
    }
}
