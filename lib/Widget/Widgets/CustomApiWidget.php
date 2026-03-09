<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class CustomApiWidget extends AbstractWidget {

    public function getId(): string { return 'customapi'; }
    public function getLabel(): string { return 'Custom API'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'url', 'label' => 'API URL (optional)', 'type' => 'text', 'required' => false, 'placeholder' => 'https://...'],
            ['key' => 'method', 'label' => 'HTTP Method', 'type' => 'select', 'required' => false, 'options' => ['GET', 'POST']],
            ['key' => 'auth_header', 'label' => 'Authorization Header', 'type' => 'password', 'required' => false, 'placeholder' => 'Basic abc... / Bearer xyz...'],
            ['key' => 'mappings', 'label' => 'Feld-Mappings', 'type' => 'mappings', 'required' => true],
        ];
    }

    public function getAllowedFields(): array { return []; }
    public function getFieldLabels(): array { return []; }

    public function getFieldLabelsForConfig(array $config): array {
        $labels = [];
        $mappings = $config['mappings'] ?? [];
        if (!is_array($mappings)) return $labels;
        foreach ($mappings as $mapping) {
            if (!empty($mapping['field']) && !empty($mapping['label'])) {
                $labels[$mapping['field']] = $mapping['label'];
            }
        }
        return $labels;
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $url = !empty($config['url']) ? $config['url'] : rtrim($baseUrl, '/');
        $method = !empty($config['method']) && in_array(strtoupper($config['method']), ['GET', 'POST']) ? strtoupper($config['method']) : 'GET';
        $headers = [];
        if (!empty($config['auth_header'])) {
            $headers[] = 'Authorization: ' . $config['auth_header'];
        }
        return [[
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
        ]];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        $mappings = $config['mappings'] ?? [];
        if (!is_array($mappings)) return [];

        $result = [];
        foreach ($mappings as $mapping) {
            $field = $mapping['field'] ?? '';
            $label = $mapping['label'] ?? '';
            if ($field === '' || $label === '') continue;
            $result[$field] = $this->resolveFieldPath($data, $field);
        }
        return $result;
    }

    /**
     * Resolve a dot-notation path against a nested array.
     * Supports array indices, e.g. "os_distribution.0.name"
     */
    private function resolveFieldPath(mixed $data, string $path): mixed {
        $segments = explode('.', $path);
        $current = $data;
        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } elseif (is_array($current) && ctype_digit($segment) && array_key_exists((int)$segment, $current)) {
                $current = $current[(int)$segment];
            } else {
                return '—';
            }
        }
        return $current;
    }
}
