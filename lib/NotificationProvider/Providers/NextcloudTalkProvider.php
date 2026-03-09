<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class NextcloudTalkProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'nextcloud_talk'; }
    public function getLabel(): string { return 'Nextcloud Talk'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'server_url', 'label' => 'Server URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://cloud.example.com'],
            ['key' => 'room_token', 'label' => 'Room token', 'type' => 'text', 'required' => true],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $url = rtrim($config['server_url'], '/') . '/ocs/v2.php/apps/spreed/api/v1/chat/' . urlencode($config['room_token']);
        $auth = base64_encode($config['username'] . ':' . $config['password']);

        $result = $this->jsonPost($url, [
            'message' => "**{$title}**\n{$message}",
        ], [
            'Authorization: Basic ' . $auth,
            'OCS-APIRequest: true',
        ]);
        $this->assertSuccess($result);
    }
}
