<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class MailcowWidget extends AbstractWidget {

    public function getId(): string { return 'mailcow'; }
    public function getLabel(): string { return 'Mailcow'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['domains', 'mailboxes', 'messages']; }

    public function getFieldLabels(): array {
        return ['domains' => 'Domains', 'mailboxes' => 'Mailboxes', 'messages' => 'Messages'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-API-Key: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/get/domain/all', 'headers' => $headers],
            ['url' => $base . '/api/v1/get/mailbox/all', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $domains = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $mailboxes = $responses[1] ?? [];
        $mboxCount = is_array($mailboxes) ? count($mailboxes) : 0;
        $messages = 0;
        if (is_array($mailboxes)) {
            foreach ($mailboxes as $mb) {
                $messages += (int)($mb['messages'] ?? 0);
            }
        }
        return [
            'domains' => (string)$domains,
            'mailboxes' => (string)$mboxCount,
            'messages' => (string)$messages,
        ];
    }
}
