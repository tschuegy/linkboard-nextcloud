<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class FritzboxWidget extends AbstractWidget {

    public function getId(): string { return 'fritzbox'; }
    public function getLabel(): string { return 'Fritz!Box'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'placeholder' => 'Optional'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => ''],
        ];
    }

    public function getAllowedFields(): array { return ['externalIp', 'uptime', 'maxDown', 'maxUp']; }

    public function getFieldLabels(): array {
        return ['externalIp' => 'External IP', 'uptime' => 'Uptime', 'maxDown' => 'Max Down', 'maxUp' => 'Max Up'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        $headers = ['Content-Type: text/xml; charset="utf-8"', 'SoapAction: urn:schemas-upnp-org:service:WANIPConnection:1#GetExternalIPAddress'];
        $auth = [];
        if (!empty($config['username'])) {
            $auth[] = 'Authorization: Basic ' . base64_encode($config['username'] . ':' . ($config['password'] ?? ''));
        }
        return [
            ['url' => $base . '/igdupnp/control/WANIPConn1', 'method' => 'POST', 'headers' => array_merge($headers, $auth),
                'body' => '<?xml version="1.0"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetExternalIPAddress xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1"/></s:Body></s:Envelope>'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        return [
            'externalIp' => '—',
            'uptime' => '—',
            'maxDown' => '—',
            'maxUp' => '—',
        ];
    }
}
