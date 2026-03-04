<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class VikunjaWidget extends AbstractWidget {

    public function getId(): string { return 'vikunja'; }
    public function getLabel(): string { return 'Vikunja'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Token', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['projects', 'tasks', 'teams']; }

    public function getFieldLabels(): array {
        return ['projects' => 'Projects', 'tasks' => 'Tasks', 'teams' => 'Teams'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Authorization: Bearer ' . ($config['api_key'] ?? '')];
        return [
            ['url' => $base . '/api/v1/projects', 'headers' => $headers],
            ['url' => $base . '/api/v1/tasks/all?page=1&per_page=1', 'headers' => $headers],
            ['url' => $base . '/api/v1/teams', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $projects = is_array($responses[0] ?? null) ? count($responses[0]) : 0;
        $tasks = is_array($responses[1] ?? null) ? count($responses[1]) : 0;
        $teams = is_array($responses[2] ?? null) ? count($responses[2]) : 0;
        return [
            'projects' => (string)$projects,
            'tasks' => (string)$tasks,
            'teams' => (string)$teams,
        ];
    }
}
