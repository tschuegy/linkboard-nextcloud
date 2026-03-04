<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class MastodonWidget extends AbstractWidget {

    public function getId(): string { return 'mastodon'; }
    public function getLabel(): string { return 'Mastodon'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Access Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['followers', 'following', 'posts']; }

    public function getFieldLabels(): array {
        return ['followers' => 'Followers', 'following' => 'Following', 'posts' => 'Posts'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/accounts/verify_credentials', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'followers' => (string)($data['followers_count'] ?? 0),
            'following' => (string)($data['following_count'] ?? 0),
            'posts' => (string)($data['statuses_count'] ?? 0),
        ];
    }
}
