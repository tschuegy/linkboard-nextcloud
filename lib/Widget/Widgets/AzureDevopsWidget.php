<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class AzureDevopsWidget extends AbstractWidget {

    public function getId(): string { return 'azuredevops'; }
    public function getLabel(): string { return 'Azure DevOps'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'PAT', 'type' => 'password', 'required' => true, 'placeholder' => 'Personal Access Token'],
            ['key' => 'organization', 'label' => 'Organization', 'type' => 'text', 'required' => true, 'placeholder' => ''],
            ['key' => 'project', 'label' => 'Project', 'type' => 'text', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['pipelines', 'runs', 'pullRequests']; }

    public function getFieldLabels(): array {
        return ['pipelines' => 'Pipelines', 'runs' => 'Runs', 'pullRequests' => 'PRs'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $org = $config['organization'] ?? '';
        $project = $config['project'] ?? '';
        $auth = 'Basic ' . base64_encode(':' . ($config['api_key'] ?? ''));
        $headers = ['Authorization: ' . $auth];
        $base = 'https://dev.azure.com/' . $org . '/' . $project;
        return [
            ['url' => $base . '/_apis/pipelines?api-version=7.0', 'headers' => $headers],
            ['url' => $base . '/_apis/build/builds?api-version=7.0&$top=1', 'headers' => $headers],
            ['url' => $base . '/_apis/git/pullrequests?api-version=7.0&searchCriteria.status=active', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $pipelines = $responses[0]['count'] ?? 0;
        $runs = $responses[1]['count'] ?? 0;
        $prs = $responses[2]['count'] ?? 0;
        return [
            'pipelines' => (string)$pipelines,
            'runs' => (string)$runs,
            'pullRequests' => (string)$prs,
        ];
    }
}
