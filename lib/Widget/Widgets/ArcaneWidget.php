<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ArcaneWidget extends AbstractWidget {

    public function getId(): string { return 'arcane'; }
    public function getLabel(): string { return 'Arcane'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'environment_id', 'label' => 'Environment ID', 'type' => 'text', 'required' => false, 'placeholder' => 'Leave empty to auto-detect'],
        ];
    }

    public function getAllowedFields(): array {
        return ['containers', 'projects', 'images', 'volumes', 'networks', 'environment'];
    }

    public function getFieldLabels(): array {
        return [
            'containers' => 'Containers',
            'projects' => 'Projects',
            'images' => 'Images',
            'volumes' => 'Volumes',
            'networks' => 'Networks',
            'environment' => 'Environment',
        ];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['X-API-Key: ' . ($config['api_key'] ?? '')];
        $envId = $config['environment_id'] ?? '';

        $requests = [
            ['url' => $base . '/api/environments', 'headers' => $headers],
        ];

        if ($envId !== '') {
            $requests = array_merge($requests, $this->buildCountRequests($base, $headers, $envId));
        }

        return $requests;
    }

    public function buildFollowUpRequests(array $responses, string $baseUrl, array $config): array {
        $envId = $config['environment_id'] ?? '';
        if ($envId !== '') {
            return [];
        }

        // Extract first environment ID from paginated response
        $envList = $responses[0]['data'] ?? $responses[0] ?? [];
        if (is_array($envList) && isset($envList[0]['id'])) {
            $envId = (string)$envList[0]['id'];
        } elseif (is_array($envList) && isset($envList['id'])) {
            $envId = (string)$envList['id'];
        } else {
            return [];
        }

        $base = rtrim($baseUrl, '/');
        $headers = ['X-API-Key: ' . ($config['api_key'] ?? '')];
        return $this->buildCountRequests($base, $headers, $envId);
    }

    public function mapResponse(array $responses, array $config): array {
        // Extract environment name
        $envResponse = $responses[0]['data'] ?? $responses[0] ?? [];
        $envId = $config['environment_id'] ?? '';
        $envName = 'Unknown';

        if (is_array($envResponse)) {
            if ($envId !== '' && isset($envResponse[0])) {
                // Find matching environment by ID
                foreach ($envResponse as $env) {
                    if (isset($env['id']) && (string)$env['id'] === $envId) {
                        $envName = $env['name'] ?? 'Unknown';
                        break;
                    }
                }
                if ($envName === 'Unknown') {
                    $envName = $envResponse[0]['name'] ?? 'Unknown';
                }
            } elseif (isset($envResponse[0]['name'])) {
                $envName = $envResponse[0]['name'];
            } elseif (isset($envResponse['name'])) {
                $envName = $envResponse['name'];
            }
        }

        // Extract counts from dedicated count endpoints
        // Each response is wrapped in { "success": true, "data": { ... } }
        $containerCounts = $this->extractData($responses[1] ?? []);
        $projectCounts = $this->extractData($responses[2] ?? []);
        $imageCounts = $this->extractData($responses[3] ?? []);
        $volumeCounts = $this->extractData($responses[4] ?? []);
        $networkCounts = $this->extractData($responses[5] ?? []);

        $cRunning = $containerCounts['running'] ?? 0;
        $cStopped = $containerCounts['stopped'] ?? 0;

        $pRunning = $projectCounts['running'] ?? 0;
        $pStopped = $projectCounts['stopped'] ?? 0;

        $imageTotal = $imageCounts['total'] ?? array_sum(array_filter($imageCounts, 'is_int'));
        $volumeTotal = $volumeCounts['total'] ?? array_sum(array_filter($volumeCounts, 'is_int'));
        $networkTotal = $networkCounts['total'] ?? array_sum(array_filter($networkCounts, 'is_int'));

        return [
            'environment' => $envName,
            'containers' => $cRunning . ' running / ' . $cStopped . ' stopped',
            'projects' => $pRunning . ' running / ' . $pStopped . ' stopped',
            'images' => (string)$imageTotal . ' total',
            'volumes' => (string)$volumeTotal . ' total',
            'networks' => (string)$networkTotal . ' total',
        ];
    }

    private function buildCountRequests(string $base, array $headers, string $envId): array {
        $prefix = $base . '/api/environments/' . $envId;
        return [
            ['url' => $prefix . '/containers/counts', 'headers' => $headers],
            ['url' => $prefix . '/projects/counts', 'headers' => $headers],
            ['url' => $prefix . '/images/counts', 'headers' => $headers],
            ['url' => $prefix . '/volumes/counts', 'headers' => $headers],
            ['url' => $prefix . '/networks/counts', 'headers' => $headers],
        ];
    }

    /**
     * Extract the data field from an Arcane API response wrapper.
     */
    private function extractData(array $response): array {
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }
        return $response;
    }
}
