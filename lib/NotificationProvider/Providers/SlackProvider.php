<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class SlackProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'slack'; }
    public function getLabel(): string { return 'Slack'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://hooks.slack.com/services/...'],
            ['key' => 'channel', 'label' => 'Channel (optional)', 'type' => 'text', 'required' => false, 'placeholder' => '#alerts'],
            ['key' => 'username', 'label' => 'Username (optional)', 'type' => 'text', 'required' => false, 'placeholder' => 'LinkBoard'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $payload = [
            'text' => "*{$title}*\n{$message}",
        ];
        if (!empty($config['channel'])) {
            $payload['channel'] = $config['channel'];
        }
        if (!empty($config['username'])) {
            $payload['username'] = $config['username'];
        }

        $result = $this->jsonPost($config['webhook_url'], $payload);
        $this->assertSuccess($result);
    }
}
