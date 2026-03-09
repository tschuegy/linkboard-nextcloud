<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class ResendProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'resend'; }
    public function getLabel(): string { return 'Resend'; }
    public function getCategory(): string { return 'email'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'required' => true],
            ['key' => 'from_email', 'label' => 'From e-mail', 'type' => 'text', 'required' => true, 'placeholder' => 'linkboard@example.com'],
            ['key' => 'to_email', 'label' => 'To e-mail', 'type' => 'text', 'required' => true],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $result = $this->jsonPost('https://api.resend.com/emails', [
            'from' => $config['from_email'],
            'to' => [$config['to_email']],
            'subject' => $title,
            'text' => $message,
        ], [
            'Authorization: Bearer ' . $config['api_key'],
        ]);
        $this->assertSuccess($result);
    }
}
