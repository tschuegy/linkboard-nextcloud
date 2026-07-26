<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

/**
 * Reads WAN status from the IGD/UPnP interface every FRITZ!Box exposes on port
 * 49000. The three actions used are readable without credentials as long as
 * "Transmit status information over UPnP" is enabled on the box.
 */
class FritzboxWidget extends AbstractWidget {

    /** TR-064 and IGD listen here, never on the web interface port. */
    private const TR064_PORT = 49000;

    private const SOAP_ENVELOPE_NS = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const UPNP_SERVICE_NS = 'urn:schemas-upnp-org:service:';

    /** The WAN states IGD defines. Anything else is not repeated back into the tile. */
    private const CONNECTION_STATES = [
        'Unconfigured', 'Connecting', 'Authenticating', 'Connected',
        'PendingDisconnect', 'Disconnecting', 'Disconnected',
    ];

    public function getId(): string { return 'fritzbox'; }
    public function getLabel(): string { return 'Fritz!Box'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'placeholder' => 'Usually not needed'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => false, 'placeholder' => 'Only if UPnP status transfer is off'],
        ];
    }

    public function getAllowedFields(): array { return ['externalIp', 'uptime', 'maxDown', 'maxUp']; }

    public function getFieldLabels(): array {
        return ['externalIp' => 'External IP', 'uptime' => 'Uptime', 'maxDown' => 'Max Down', 'maxUp' => 'Max Up'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $endpoint = $this->soapEndpoint($baseUrl);
        $auth = [
            'username' => (string)($config['username'] ?? ''),
            'password' => (string)($config['password'] ?? ''),
        ];

        return [
            $this->soapRequest($endpoint . '/igdupnp/control/WANIPConn1', 'WANIPConnection:1', 'GetExternalIPAddress', $auth),
            $this->soapRequest($endpoint . '/igdupnp/control/WANIPConn1', 'WANIPConnection:1', 'GetStatusInfo', $auth),
            $this->soapRequest($endpoint . '/igdupnp/control/WANCommonIFC1', 'WANCommonInterfaceConfig:1', 'GetCommonLinkProperties', $auth),
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $externalIp = trim((string)($responses[0]['NewExternalIPAddress'] ?? ''));
        $uptime = (int)($responses[1]['NewUptime'] ?? 0);
        $maxDown = (int)($responses[2]['NewLayer1DownstreamMaxBitRate'] ?? 0);
        $maxUp = (int)($responses[2]['NewLayer1UpstreamMaxBitRate'] ?? 0);

        $result = [
            // A disconnected box reports 0.0.0.0 rather than an empty value.
            'externalIp' => ($externalIp === '' || $externalIp === '0.0.0.0') ? '—' : $externalIp,
            'uptime' => $this->formatUptime($uptime),
            'maxDown' => $this->formatBitRate($maxDown),
            'maxUp' => $this->formatBitRate($maxUp),
        ];

        $warning = $this->connectionWarning(trim((string)($responses[1]['NewConnectionStatus'] ?? '')));
        if ($warning !== null) {
            $result['_warning'] = $warning;
        }

        return $result;
    }

    /**
     * A box that does not run the internet connection itself answers every WAN
     * action with empty values, which would leave four unexplained dashes on the
     * tile. The status the box reports alongside them says why (issue #11).
     */
    private function connectionWarning(string $status): ?string {
        if ($status === '' || $status === 'Connected') {
            return null;
        }

        if (!in_array($status, self::CONNECTION_STATES, true)) {
            return 'The FRITZ!Box reports no WAN connection, so the WAN values stay empty.';
        }

        if ($status === 'Unconfigured') {
            return 'The FRITZ!Box reports WAN status "Unconfigured": it has no internet connection of its own'
                . ' (IP client or bridge mode), so the WAN values stay empty.';
        }

        return 'The FRITZ!Box reports WAN status "' . $status . '", so the WAN values stay empty.';
    }

    /**
     * Rebuild the service URL as a TR-064 endpoint: the host it names, port
     * 49000 unless the user pinned one, plain HTTP because the TLS port serves
     * a self-signed certificate that outbound verification rejects.
     */
    private function soapEndpoint(string $baseUrl): string {
        $raw = trim($baseUrl);
        if (preg_match('#^https?://#i', $raw) !== 1) {
            $raw = 'http://' . $raw;
        }

        $parts = parse_url($raw);
        $host = is_array($parts) ? (string)($parts['host'] ?? '') : '';
        $port = is_array($parts) ? (int)($parts['port'] ?? self::TR064_PORT) : self::TR064_PORT;

        return 'http://' . $host . ':' . $port;
    }

    /** @param array{username: string, password: string} $auth */
    private function soapRequest(string $url, string $service, string $action, array $auth): array {
        $serviceUrn = self::UPNP_SERVICE_NS . $service;

        return [
            'url' => $url,
            'method' => 'POST',
            'headers' => [
                'Content-Type: text/xml; charset="utf-8"',
                'SoapAction: ' . $serviceUrn . '#' . $action,
            ],
            'body' => '<?xml version="1.0"?>'
                . '<s:Envelope xmlns:s="' . self::SOAP_ENVELOPE_NS . '" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
                . '<s:Body><u:' . $action . ' xmlns:u="' . $serviceUrn . '"/></s:Body></s:Envelope>',
            '_response_format' => 'xml',
            '_http_auth' => $auth,
        ];
    }

    private function formatUptime(int $seconds): string {
        if ($seconds <= 0) {
            return '—';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        if ($days > 0) {
            return $days . 'd ' . $hours . 'h';
        }

        $minutes = intdiv($seconds % 3600, 60);
        return $hours > 0 ? $hours . 'h ' . $minutes . 'm' : $minutes . 'm';
    }

    private function formatBitRate(int $bitsPerSecond): string {
        return $bitsPerSecond > 0 ? round($bitsPerSecond / 1000000, 1) . ' Mbps' : '—';
    }
}
