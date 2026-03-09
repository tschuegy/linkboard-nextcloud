<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class BrevoProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'brevo'; }
    public function getLabel(): string { return 'Brevo'; }
    public function getCategory(): string { return 'email'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'required' => true],
            ['key' => 'sender_email', 'label' => 'Sender e-mail', 'type' => 'text', 'required' => true, 'placeholder' => 'linkboard@example.com'],
            ['key' => 'recipient_email', 'label' => 'Recipient e-mail', 'type' => 'text', 'required' => true],
            ['key' => 'sender_name', 'label' => 'Sender name', 'type' => 'text', 'required' => false, 'placeholder' => 'LinkBoard'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $result = $this->jsonPost('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => $config['sender_name'] ?? 'LinkBoard',
                'email' => $config['sender_email'],
            ],
            'to' => [['email' => $config['recipient_email']]],
            'subject' => $title,
            'textContent' => $message,
        ], [
            'api-key: ' . $config['api_key'],
        ]);
        $this->assertSuccess($result);
    }
}
