<?php

declare(strict_types=1);

// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * SOAP envelopes as a FRITZ!Box returns them on its IGD interface, keyed by action.
 *
 * Shared by the mock server (tests/fixtures/fritzbox_tr064_mock.php) and the widget
 * test (tests/widget/fritzbox_widget.php) so both exercise identical payloads.
 *
 * Sample line: 250/50 Mbit/s, connected for 1234567 seconds (14d 6h).
 *
 * @return array<string, string>
 */
return [
    'GetExternalIPAddress' => '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetExternalIPAddressResponse xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1">'
        . '<NewExternalIPAddress>84.130.12.34</NewExternalIPAddress>'
        . '</u:GetExternalIPAddressResponse>'
        . '</s:Body></s:Envelope>',

    'GetStatusInfo' => '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetStatusInfoResponse xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1">'
        . '<NewConnectionStatus>Connected</NewConnectionStatus>'
        . '<NewLastConnectionError>ERROR_NONE</NewLastConnectionError>'
        . '<NewUptime>1234567</NewUptime>'
        . '</u:GetStatusInfoResponse>'
        . '</s:Body></s:Envelope>',

    // Captured from real FRITZ!OS hardware (issue #11): a box that does not run the
    // internet connection itself answers GetStatusInfo with exactly these values.
    // Not served by the mock — the SoapAction it keys off is GetStatusInfo either way;
    // swap it in there by hand to reproduce such a box end to end.
    'GetStatusInfoUnconfigured' => '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetStatusInfoResponse xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1">'
        . '<NewConnectionStatus>Unconfigured</NewConnectionStatus>'
        . '<NewLastConnectionError>ERROR_NONE</NewLastConnectionError>'
        . '<NewUptime>0</NewUptime>'
        . '</u:GetStatusInfoResponse>'
        . '</s:Body></s:Envelope>',

    'GetCommonLinkProperties' => '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetCommonLinkPropertiesResponse xmlns:u="urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1">'
        . '<NewWANAccessType>DSL</NewWANAccessType>'
        . '<NewLayer1UpstreamMaxBitRate>49792000</NewLayer1UpstreamMaxBitRate>'
        . '<NewLayer1DownstreamMaxBitRate>249856000</NewLayer1DownstreamMaxBitRate>'
        . '<NewPhysicalLinkStatus>Up</NewPhysicalLinkStatus>'
        . '</u:GetCommonLinkPropertiesResponse>'
        . '</s:Body></s:Envelope>',
];
