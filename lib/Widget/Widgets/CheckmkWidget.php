<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class CheckmkWidget extends AbstractWidget {

    public function getId(): string { return 'checkmk'; }
    public function getLabel(): string { return 'Checkmk'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'api_key', 'label' => 'Automation Secret', 'type' => 'password', 'required' => true, 'placeholder' => ''],
            ['key' => 'username', 'label' => 'Automation User', 'type' => 'text', 'required' => true, 'placeholder' => 'automation'],
            ['key' => 'site', 'label' => 'Site Name', 'type' => 'text', 'required' => true, 'placeholder' => 'cmk'],
        ];
    }

    public function getAllowedFields(): array { return ['hostsUp', 'hostsDown', 'servicesOk', 'servicesCritical']; }

    public function getFieldLabels(): array {
        return ['hostsUp' => 'Hosts Up', 'hostsDown' => 'Hosts Down', 'servicesOk' => 'Services OK', 'servicesCritical' => 'Critical'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $site = $config['site'] ?? 'cmk';
        $headers = [
            'Authorization: Bearer ' . ($config['username'] ?? '') . ' ' . ($config['api_key'] ?? ''),
            'Accept: application/json',
        ];
        return [
            ['url' => $base . '/' . $site . '/check_mk/api/1.0/domain-types/host/collections/all', 'headers' => $headers],
            ['url' => $base . '/' . $site . '/check_mk/api/1.0/domain-types/service/collections/all?columns=state', 'headers' => $headers],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $hosts = $responses[0]['value'] ?? [];
        $hostsUp = 0; $hostsDown = 0;
        if (is_array($hosts)) {
            foreach ($hosts as $h) {
                $state = $h['extensions']['attributes']['state'] ?? 0;
                if ($state == 0) $hostsUp++;
                else $hostsDown++;
            }
        }
        $services = $responses[1]['value'] ?? [];
        $ok = 0; $critical = 0;
        if (is_array($services)) {
            foreach ($services as $s) {
                $state = $s['extensions']['state'] ?? 0;
                if ($state == 0) $ok++;
                elseif ($state == 2) $critical++;
            }
        }
        return [
            'hostsUp' => (string)$hostsUp,
            'hostsDown' => (string)$hostsDown,
            'servicesOk' => (string)$ok,
            'servicesCritical' => (string)$critical,
        ];
    }
}
