<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class MicrosoftTeamsProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'microsoft_teams'; }
    public function getLabel(): string { return 'Microsoft Teams'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://outlook.office.com/webhook/...'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $result = $this->jsonPost($config['webhook_url'], [
            'type' => 'message',
            'attachments' => [[
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'content' => [
                    '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                    'type' => 'AdaptiveCard',
                    'version' => '1.4',
                    'body' => [
                        ['type' => 'TextBlock', 'text' => $title, 'weight' => 'Bolder', 'size' => 'Medium'],
                        ['type' => 'TextBlock', 'text' => $message, 'wrap' => true],
                    ],
                ],
            ]],
        ]);
        $this->assertSuccess($result);
    }
}
