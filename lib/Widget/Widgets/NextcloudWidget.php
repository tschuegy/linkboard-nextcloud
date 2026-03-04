<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class NextcloudWidget extends AbstractWidget {

    public function getId(): string { return 'nextcloud'; }
    public function getLabel(): string { return 'Nextcloud'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Admin Username', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'password', 'label' => 'Admin Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['activeUsers', 'totalFiles', 'freeSpace']; }

    public function getFieldLabels(): array {
        return ['activeUsers' => 'Active Users', 'totalFiles' => 'Files', 'freeSpace' => 'Free Space'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $auth = 'Basic ' . base64_encode(($config['username'] ?? '') . ':' . ($config['password'] ?? ''));
        $headers = ['Authorization: ' . $auth, 'OCS-APIRequest: true'];
        return [
            ['url' => $base . '/ocs/v2.php/cloud/users?format=json', 'headers' => $headers],
            ['url' => $base . '/ocs/v2.php/apps/serverinfo/api/v1/info?format=json', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $users = $responses[0]['ocs']['data']['users'] ?? [];
        $activeUsers = is_array($users) ? count($users) : 0;
        $serverInfo = $responses[1]['ocs']['data'] ?? [];
        $totalFiles = $serverInfo['nextcloud']['storage']['num_files'] ?? 0;
        $freeSpace = (float)($serverInfo['nextcloud']['system']['freespace'] ?? 0);
        $gb = round($freeSpace / 1073741824, 1);
        return [
            'activeUsers' => (string)$activeUsers,
            'totalFiles' => (string)$totalFiles,
            'freeSpace' => $gb . ' GB',
        ];
    }
}
