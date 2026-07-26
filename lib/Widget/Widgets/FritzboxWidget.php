<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

/**
 * Reads WAN status from the interfaces every FRITZ!Box serves on port 49000.
 *
 * IGD splits WAN connections across two services and a box only fills in the one
 * matching its connection type: WANIPConnection carries IP-routed connections,
 * WANPPPConnection carries PPPoE. The credential-free IGD service is asked first;
 * a box that answers it with "Unconfigured" is then probed for a PPP connection,
 * which FRITZ!OS publishes on its TR-064 interface (issue #11).
 */
class FritzboxWidget extends AbstractWidget {

    /** TR-064 and IGD listen here, never on the web interface port. */
    private const TR064_PORT = 49000;

    private const SOAP_ENVELOPE_NS = 'http://schemas.xmlsoap.org/soap/envelope/';

    /** The IGD tree on /igdupnp/…, readable without credentials. */
    private const IGD_SERVICE_NS = 'urn:schemas-upnp-org:service:';

    /** The TR-064 tree on /upnp/…, which always demands HTTP digest credentials. */
    private const TR064_SERVICE_NS = 'urn:dslforum-org:service:';

    /** The WAN states IGD defines. Anything else is not repeated back into the tile. */
    private const CONNECTION_STATES = [
        'Unconfigured', 'Connecting', 'Authenticating', 'Connected',
        'PendingDisconnect', 'Disconnecting', 'Disconnected',
    ];

    public function getId(): string { return 'fritzbox'; }
    public function getLabel(): string { return 'Fritz!Box'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'placeholder' => 'FRITZ!Box user, for PPPoE boxes'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => false, 'placeholder' => 'Needed for PPPoE connections'],
        ];
    }

    public function getAllowedFields(): array { return ['externalIp', 'uptime', 'maxDown', 'maxUp']; }

    public function getFieldLabels(): array {
        return ['externalIp' => 'External IP', 'uptime' => 'Uptime', 'maxDown' => 'Max Down', 'maxUp' => 'Max Up'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $endpoint = $this->soapEndpoint($baseUrl);
        $auth = $this->credentials($config);

        return [
            $this->soapRequest($endpoint . '/igdupnp/control/WANIPConn1', self::IGD_SERVICE_NS . 'WANIPConnection:1', 'GetExternalIPAddress', $auth),
            $this->soapRequest($endpoint . '/igdupnp/control/WANIPConn1', self::IGD_SERVICE_NS . 'WANIPConnection:1', 'GetStatusInfo', $auth),
            $this->soapRequest($endpoint . '/igdupnp/control/WANCommonIFC1', self::IGD_SERVICE_NS . 'WANCommonInterfaceConfig:1', 'GetCommonLinkProperties', $auth),
        ];
    }

    /**
     * A box whose internet connection runs over PPPoE reports "Unconfigured" on
     * WANIPConnection — its actual WAN data sits on WANPPPConnection. Both places
     * that service can live are probed, and both probes are optional: a box that
     * does not implement one answers with an error, which is an answer, not a
     * failure.
     *
     * Only the status decides whether to probe: a real box hands out the external
     * IP on WANIPConnection even while calling that service Unconfigured (issue
     * #11, FRITZ!Box 7590 on 154.08.20), so present values prove nothing. Values
     * without any status still count — a box that names no state cannot be asked
     * more precisely.
     */
    public function buildFollowUpRequests(array $responses, string $baseUrl, array $config): array {
        $igd = $this->pickConnection($this->connectionViews($responses));
        if ($igd['status'] === 'Connected' || ($igd['status'] === '' && $this->hasValues($igd))) {
            return [];
        }

        $endpoint = $this->soapEndpoint($baseUrl);
        $auth = $this->credentials($config);

        $probes = [
            $this->soapRequest($endpoint . '/igdupnp/control/WANPPPConn1', self::IGD_SERVICE_NS . 'WANPPPConnection:1', 'GetInfo', $auth, true),
        ];

        // TR-064 answers nothing without credentials, so asking is only worth a
        // request once the user has supplied them.
        if ($auth['password'] !== '') {
            $probes[] = $this->soapRequest($endpoint . '/upnp/control/wanpppconn1', self::TR064_SERVICE_NS . 'WANPPPConnection:1', 'GetInfo', $auth, true);
        }

        return $probes;
    }

    public function mapResponse(array $responses, array $config): array {
        $connection = $this->pickConnection($this->connectionViews($responses));

        // The connection service reports the rate it negotiated; where it reports
        // none, the physical line rate from WANCommonInterfaceConfig stands in.
        $maxDown = $connection['down'] > 0 ? $connection['down'] : (int)($responses[2]['NewLayer1DownstreamMaxBitRate'] ?? 0);
        $maxUp = $connection['up'] > 0 ? $connection['up'] : (int)($responses[2]['NewLayer1UpstreamMaxBitRate'] ?? 0);

        $result = [
            // A disconnected box reports 0.0.0.0 rather than an empty value.
            'externalIp' => $connection['ip'] === '' || $connection['ip'] === '0.0.0.0' ? '—' : $connection['ip'],
            'uptime' => $this->formatUptime($connection['uptime']),
            'maxDown' => $this->formatBitRate($maxDown),
            'maxUp' => $this->formatBitRate($maxUp),
        ];

        $warning = $this->connectionWarning($connection['status'], $this->credentials($config)['password'] !== '');
        if ($warning !== null) {
            $result['_warning'] = $warning;
        }

        return $result;
    }

    /**
     * The WAN connection as each service that could carry it describes it: the IGD
     * WANIPConnection pair first, then one view per PPP probe that answered.
     *
     * @return non-empty-list<array{status: string, ip: string, uptime: int, down: int, up: int}>
     */
    private function connectionViews(array $responses): array {
        $views = [[
            'status' => trim((string)($responses[1]['NewConnectionStatus'] ?? '')),
            'ip' => trim((string)($responses[0]['NewExternalIPAddress'] ?? '')),
            'uptime' => (int)($responses[1]['NewUptime'] ?? 0),
            'down' => (int)($responses[2]['NewLayer1DownstreamMaxBitRate'] ?? 0),
            'up' => (int)($responses[2]['NewLayer1UpstreamMaxBitRate'] ?? 0),
        ]];

        // Every GetInfo reply carries the whole connection on its own. The probes
        // sit at fixed positions — a probe the box refused is an empty array, not
        // a gap: the IGD probe first, the TR-064 probe second. IGD reports its
        // rates in bit/s as UPnP specifies; AVM's TR-064 reports kbit/s (issue
        // #11: a real box answered 226415 on a 226 Mbit/s line).
        foreach (array_slice($responses, 3) as $index => $probe) {
            if (!is_array($probe) || $probe === []) {
                continue;
            }

            $rateFactor = $index === 1 ? 1000 : 1;
            $views[] = [
                'status' => trim((string)($probe['NewConnectionStatus'] ?? '')),
                'ip' => trim((string)($probe['NewExternalIPAddress'] ?? '')),
                'uptime' => (int)($probe['NewUptime'] ?? 0),
                'down' => (int)($probe['NewDownstreamMaxBitRate'] ?? 0) * $rateFactor,
                'up' => (int)($probe['NewUpstreamMaxBitRate'] ?? 0) * $rateFactor,
            ];
        }

        return $views;
    }

    /**
     * @param  non-empty-list<array{status: string, ip: string, uptime: int, down: int, up: int}> $views
     * @return array{status: string, ip: string, uptime: int, down: int, up: int}
     */
    private function pickConnection(array $views): array {
        foreach ($views as $view) {
            if ($view['status'] === 'Connected') {
                return $view;
            }
        }

        // A box may hold values without calling itself connected, e.g. while it
        // is still authenticating.
        foreach ($views as $view) {
            if ($this->hasValues($view)) {
                return $view;
            }
        }

        // Nothing to show: keep whichever service at least named a state, so the
        // tile can say why it is empty.
        foreach ($views as $view) {
            if ($view['status'] !== '') {
                return $view;
            }
        }

        return $views[0];
    }

    /** @param array{status: string, ip: string, uptime: int, down: int, up: int} $view */
    private function hasValues(array $view): bool {
        return ($view['ip'] !== '' && $view['ip'] !== '0.0.0.0') || $view['uptime'] > 0;
    }

    /**
     * A box that does not run the internet connection on the service we could read
     * answers with empty values, which would leave four unexplained dashes on the
     * tile. The status the box reports alongside them says why (issue #11).
     */
    private function connectionWarning(string $status, bool $hasCredentials): ?string {
        if ($status === '' || $status === 'Connected') {
            return null;
        }

        if (!in_array($status, self::CONNECTION_STATES, true)) {
            return 'The FRITZ!Box reports no WAN connection, so the WAN values stay empty.';
        }

        if ($status !== 'Unconfigured') {
            return 'The FRITZ!Box reports WAN status "' . $status . '", so the WAN values stay empty.';
        }

        if (!$hasCredentials) {
            return 'The FRITZ!Box reports WAN status "Unconfigured" on its UPnP interface. A connection over PPPoE'
                . ' is reported on the TR-064 interface instead — enter the box username and password to read it.';
        }

        return 'Neither the UPnP nor the TR-064 interface of the FRITZ!Box reports a WAN connection ("Unconfigured").'
            . ' Allow access for applications on the box, and use an account permitted to read its settings.';
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

    /** @return array{username: string, password: string} */
    private function credentials(array $config): array {
        return [
            'username' => (string)($config['username'] ?? ''),
            'password' => (string)($config['password'] ?? ''),
        ];
    }

    /** @param array{username: string, password: string} $auth */
    private function soapRequest(string $url, string $serviceUrn, string $action, array $auth, bool $optional = false): array {
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
            '_optional' => $optional,
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
