<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class NtfyProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'ntfy'; }
    public function getLabel(): string { return 'Ntfy'; }
    public function getCategory(): string { return 'push'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'server_url', 'label' => 'Server URL', 'type' => 'text', 'required' => false, 'placeholder' => 'https://ntfy.sh'],
            ['key' => 'topic', 'label' => 'Topic', 'type' => 'text', 'required' => true, 'placeholder' => 'linkboard-alerts'],
            ['key' => 'auth_token', 'label' => 'Auth token (optional)', 'type' => 'password', 'required' => false],
            ['key' => 'priority', 'label' => 'Priority', 'type' => 'text', 'required' => false, 'placeholder' => '3'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $server = rtrim($config['server_url'] ?? 'https://ntfy.sh', '/');
        $url = $server . '/' . urlencode($config['topic']);

        $headers = [
            'Title: ' . $title,
            'Priority: ' . ($config['priority'] ?? '3'),
        ];
        if (!empty($config['auth_token'])) {
            $headers[] = 'Authorization: Bearer ' . $config['auth_token'];
        }

        $result = $this->curlRequest($url, 'POST', $headers, $message);
        $this->assertSuccess($result);
    }
}
