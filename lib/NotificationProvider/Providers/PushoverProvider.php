<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class PushoverProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'pushover'; }
    public function getLabel(): string { return 'Pushover'; }
    public function getCategory(): string { return 'push'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'user_key', 'label' => 'User key', 'type' => 'text', 'required' => true],
            ['key' => 'app_token', 'label' => 'App token', 'type' => 'password', 'required' => true],
            ['key' => 'priority', 'label' => 'Priority', 'type' => 'select', 'required' => false, 'options' => [
                ['value' => '-2', 'label' => 'Lowest (no notification)'],
                ['value' => '-1', 'label' => 'Low (no sound)'],
                ['value' => '0', 'label' => 'Normal'],
                ['value' => '1', 'label' => 'High'],
                ['value' => '2', 'label' => 'Emergency (requires retry/expire)'],
            ]],
            ['key' => 'retry', 'label' => 'Retry (seconds)', 'type' => 'text', 'required' => false, 'placeholder' => '60', 'showWhen' => ['priority', '2']],
            ['key' => 'expire', 'label' => 'Expire (seconds)', 'type' => 'text', 'required' => false, 'placeholder' => '3600', 'showWhen' => ['priority', '2']],
            ['key' => 'device', 'label' => 'Device (optional)', 'type' => 'text', 'required' => false],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $payload = [
            'token' => $config['app_token'],
            'user' => $config['user_key'],
            'title' => $title,
            'message' => $message,
        ];
        if (!empty($config['priority'])) {
            $payload['priority'] = (int)$config['priority'];
            if ((int)$config['priority'] === 2) {
                $payload['retry'] = !empty($config['retry']) ? (int)$config['retry'] : 60;
                $payload['expire'] = !empty($config['expire']) ? (int)$config['expire'] : 3600;
            }
        }
        if (!empty($config['device'])) {
            $payload['device'] = $config['device'];
        }

        $result = $this->formPost('https://api.pushover.net/1/messages.json', $payload);
        $this->assertSuccess($result);
    }
}
