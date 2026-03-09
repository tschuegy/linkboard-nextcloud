<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class WebhookProvider extends AbstractNotificationProvider {

    public function getId(): string { return 'webhook'; }
    public function getLabel(): string { return 'Webhook'; }
    public function getCategory(): string { return 'universal'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'url', 'label' => 'URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://example.com/webhook'],
            ['key' => 'method', 'label' => 'HTTP Method', 'type' => 'text', 'required' => false, 'placeholder' => 'POST'],
            ['key' => 'content_type', 'label' => 'Content-Type', 'type' => 'text', 'required' => false, 'placeholder' => 'application/json'],
            ['key' => 'headers', 'label' => 'Headers (JSON)', 'type' => 'text', 'required' => false, 'placeholder' => '{"Authorization": "Bearer ..."}'],
            ['key' => 'body', 'label' => 'Body template', 'type' => 'textarea', 'required' => false, 'placeholder' => '{"text": "{{title}}: {{message}}"}'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $url = $config['url'];
        $method = $config['method'] ?? 'POST';
        $contentType = $config['content_type'] ?? 'application/json';

        $headers = ['Content-Type: ' . $contentType];
        if (!empty($config['headers'])) {
            $extra = json_decode($config['headers'], true);
            if (is_array($extra)) {
                foreach ($extra as $k => $v) {
                    $headers[] = $k . ': ' . $v;
                }
            }
        }

        $body = $config['body'] ?? '{"title": "{{title}}", "message": "{{message}}"}';
        $body = str_replace(['{{title}}', '{{message}}'], [$title, $message], $body);

        $result = $this->curlRequest($url, $method, $headers, $body);
        $this->assertSuccess($result);
    }
}
