<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class MatrixProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'matrix'; }
    public function getLabel(): string { return 'Matrix'; }
    public function getCategory(): string { return 'chat'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'homeserver_url', 'label' => 'Homeserver URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://matrix.example.com'],
            ['key' => 'access_token', 'label' => 'Access token', 'type' => 'password', 'required' => true],
            ['key' => 'internal_room_id', 'label' => 'Room ID', 'type' => 'text', 'required' => true, 'placeholder' => '!roomid:example.com'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $roomId = urlencode($config['internal_room_id']);
        $txnId = urlencode(uniqid('lb_', true));
        $url = rtrim($config['homeserver_url'], '/') . "/_matrix/client/r0/rooms/{$roomId}/send/m.room.message/{$txnId}";

        $result = $this->curlRequest($url, 'PUT', [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['access_token'],
        ], json_encode([
            'msgtype' => 'm.text',
            'body' => "{$title}\n{$message}",
        ]));
        $this->assertSuccess($result);
    }
}
