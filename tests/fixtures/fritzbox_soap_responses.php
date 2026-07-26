<?php

declare(strict_types=1);

// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * SOAP envelopes as a FRITZ!Box returns them on its IGD and TR-064 interfaces,
 * keyed by action.
 *
 * Shared by the mock server (tests/fixtures/fritzbox_tr064_mock.php) and the widget
 * test (tests/widget/fritzbox_widget.php) so both exercise identical payloads.
 *
 * Sample line: 250/50 Mbit/s, connected for 1234567 seconds (14d 6h). The PPP
 * envelopes describe the same line reached over PPPoE, where the box negotiates
 * slightly less than the physical rate.
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

    // Captured from real FRITZ!OS hardware (issue #11): a box whose internet
    // connection does not run on WANIPConnection — a PPPoE box, or one in IP client
    // or bridge mode — answers GetStatusInfo with exactly these values.
    // The mock serves it in place of GetStatusInfo under FRITZMOCK_WAN_UNCONFIGURED=1.
    'GetStatusInfoUnconfigured' => '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetStatusInfoResponse xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1">'
        . '<NewConnectionStatus>Unconfigured</NewConnectionStatus>'
        . '<NewLastConnectionError>ERROR_NONE</NewLastConnectionError>'
        . '<NewUptime>0</NewUptime>'
        . '</u:GetStatusInfoResponse>'
        . '</s:Body></s:Envelope>',

    // TR-064 WANPPPConnection:1#GetInfo — the service a box uses when its internet
    // connection runs over PPPoE. One reply carries the whole connection, which is
    // why the widget needs no second call there. Captured from real FRITZ!OS
    // hardware (issue #11), shortened to the elements the widget reads plus their
    // neighbours; addresses and account name are made up. Note the bit rates:
    // AVM reports them in kbit/s here — this line runs at 226/36 Mbit/s — while
    // the IGD tree reports bit/s.
    'GetInfo' => '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetInfoResponse xmlns:u="urn:dslforum-org:service:WANPPPConnection:1">'
        . '<NewEnable>1</NewEnable>'
        . '<NewConnectionStatus>Connected</NewConnectionStatus>'
        . '<NewPossibleConnectionTypes>IP_Routed, IP_Bridged</NewPossibleConnectionTypes>'
        . '<NewConnectionType>IP_Routed</NewConnectionType>'
        . '<NewName>internet</NewName>'
        . '<NewUptime>44494</NewUptime>'
        . '<NewUpstreamMaxBitRate>36226</NewUpstreamMaxBitRate>'
        . '<NewDownstreamMaxBitRate>226415</NewDownstreamMaxBitRate>'
        . '<NewLastConnectionError>ERROR_NONE</NewLastConnectionError>'
        . '<NewUserName>PPPoE-User</NewUserName>'
        . '<NewNATEnabled>1</NewNATEnabled>'
        . '<NewExternalIPAddress>84.130.12.34</NewExternalIPAddress>'
        . '<NewConnectionTrigger>AlwaysOn</NewConnectionTrigger>'
        . '<NewTransportType>PPPoE</NewTransportType>'
        . '</u:GetInfoResponse>'
        . '</s:Body></s:Envelope>',

    // The same action on a box that has no PPP connection either — everything at
    // its default. Not served by the mock; the widget test uses it directly.
    'GetInfoUnconfigured' => '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetInfoResponse xmlns:u="urn:dslforum-org:service:WANPPPConnection:1">'
        . '<NewEnable>0</NewEnable>'
        . '<NewConnectionStatus>Unconfigured</NewConnectionStatus>'
        . '<NewUptime>0</NewUptime>'
        . '<NewUpstreamMaxBitRate>0</NewUpstreamMaxBitRate>'
        . '<NewDownstreamMaxBitRate>0</NewDownstreamMaxBitRate>'
        . '<NewLastConnectionError>ERROR_NONE</NewLastConnectionError>'
        . '<NewExternalIPAddress></NewExternalIPAddress>'
        . '</u:GetInfoResponse>'
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

    // A box whose internal DSL modem is switched off — the line runs over an
    // external fiber modem — reports its WAN as Ethernet with the *configured*
    // tariff rates in bit/s, link up. Captured from real FRITZ!OS hardware
    // (issue #11); the PPP sync rates on WANPPPConnection are the real ones.
    'GetCommonLinkPropertiesEthernet' => '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetCommonLinkPropertiesResponse xmlns:u="urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1">'
        . '<NewWANAccessType>Ethernet</NewWANAccessType>'
        . '<NewLayer1UpstreamMaxBitRate>40000000</NewLayer1UpstreamMaxBitRate>'
        . '<NewLayer1DownstreamMaxBitRate>250000000</NewLayer1DownstreamMaxBitRate>'
        . '<NewPhysicalLinkStatus>Up</NewPhysicalLinkStatus>'
        . '</u:GetCommonLinkPropertiesResponse>'
        . '</s:Body></s:Envelope>',
];
