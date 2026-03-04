<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class GitlabWidget extends AbstractWidget {

    public function getId(): string { return 'gitlab'; }
    public function getLabel(): string { return 'GitLab'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Access Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['projects', 'users', 'groups', 'issues']; }

    public function getFieldLabels(): array {
        return ['projects' => 'Projects', 'users' => 'Users', 'groups' => 'Groups', 'issues' => 'Issues'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['PRIVATE-TOKEN: ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v4/projects?per_page=1', 'headers' => $headers],
            ['url' => $base . '/api/v4/users?per_page=1', 'headers' => $headers],
            ['url' => $base . '/api/v4/groups?per_page=1', 'headers' => $headers],
            ['url' => $base . '/api/v4/issues?per_page=1&state=opened', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        // GitLab returns counts in headers, but we have body data
        $projects = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $users = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $groups = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        $issues = is_array($responses[3] ?? null) ? count($responses[3]) : 0;
        return [
            'projects' => (string)$projects,
            'users' => (string)$users,
            'groups' => (string)$groups,
            'issues' => (string)$issues,
        ];
    }
}
