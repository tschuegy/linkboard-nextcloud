<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

/**
 * Pi-hole v6 uses a two-step session-based REST API:
 *   POST /api/auth                       → returns { session: { sid: "..." } }
 *   GET  /api/stats/summary?sid=<SID>    → returns { queries: { total, blocked, percent_blocked } }
 *
 * Implemented via buildRequests (auth) + buildFollowUpRequests (stats using the SID).
 */
class PiHoleWidget extends AbstractWidget {

    public function getId(): string { return 'pihole'; }
    public function getLabel(): string { return 'Pi-hole'; }

    public function getConfigFields(): array {
        return [
            [
                'key'         => 'password',
                'label'       => 'Admin Password',
                'type'        => 'password',
                'required'    => true,
                'placeholder' => 'Pi-hole admin or app password (required in v6)',
            ],
        ];
    }

    public function getAllowedFields(): array {
        return ['queries', 'blocked', 'percentage'];
    }

    public function getFieldLabels(): array {
        return [
            'queries'    => 'Queries',
            'blocked'    => 'Blocked',
            'percentage' => 'Blocked %',
        ];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = $this->getApiBase($baseUrl);
        return [[
            'url'     => $base . '/api/auth',
            'method'  => 'POST',
            'headers' => ['Content-Type: application/json', 'Accept: application/json'],
            'body'    => json_encode(['password' => $config['password'] ?? '']),
        ]];
    }

    public function buildFollowUpRequests(array $responses, string $baseUrl, array $config): array {
        $sid = $responses[0]['session']['sid'] ?? null;
        if (!is_string($sid) || $sid === '') {
            return [];
        }
        $base = $this->getApiBase($baseUrl);
        return [['url' => $base . '/api/stats/summary?sid=' . urlencode($sid)]];
    }

    public function mapResponse(array $responses, array $config): array {
        $stats   = is_array(end($responses)) ? end($responses) : [];
        $queries = $stats['queries'] ?? [];

        return [
            'queries'    => number_format((float)($queries['total']           ?? 0)),
            'blocked'    => number_format((float)($queries['blocked']         ?? 0)),
            'percentage' => round((float)($queries['percent_blocked']         ?? 0), 1) . '%',
        ];
    }

    private function getApiBase(string $url): string {
        $base = rtrim($url, '/');
        if (str_ends_with($base, '/admin')) {
            $base = substr($base, 0, -6);
        }
        return $base;
    }
}
