<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class SendGridProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'sendgrid'; }
    public function getLabel(): string { return 'SendGrid'; }
    public function getCategory(): string { return 'email'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'required' => true],
            ['key' => 'from_email', 'label' => 'From e-mail', 'type' => 'text', 'required' => true, 'placeholder' => 'linkboard@example.com'],
            ['key' => 'to_email', 'label' => 'To e-mail', 'type' => 'text', 'required' => true],
            ['key' => 'from_name', 'label' => 'From name', 'type' => 'text', 'required' => false, 'placeholder' => 'LinkBoard'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $from = ['email' => $config['from_email']];
        if (!empty($config['from_name'])) {
            $from['name'] = $config['from_name'];
        }

        $result = $this->jsonPost('https://api.sendgrid.com/v3/mail/send', [
            'personalizations' => [['to' => [['email' => $config['to_email']]]]],
            'from' => $from,
            'subject' => $title,
            'content' => [['type' => 'text/plain', 'value' => $message]],
        ], [
            'Authorization: Bearer ' . $config['api_key'],
        ]);
        $this->assertSuccess($result, 202);
    }
}
