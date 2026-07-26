<?php

declare(strict_types=1);

// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Stand-in for FRITZ!Box hardware: serves the IGD/TR-064 SOAP actions the
 * Fritz!Box widget calls. FRITZ!OS is not distributable as a VM image, but the
 * protocol is static, so a mock is a complete substitute for widget development.
 *
 * Start it on the host's private address — NOT on 127.0.0.1, which
 * OutboundRequestGuard::FORBIDDEN_CIDRS blocks for every outbound widget request:
 *
 *     php -S "$(hostname -I | awk '{print $1}'):49000" tests/fixtures/fritzbox_tr064_mock.php
 *
 * Then point a LinkBoard service at http://<that address> and pick the Fritz!Box
 * widget; the widget appends port 49000 itself.
 *
 * Environment flags:
 *
 *   FRITZMOCK_REQUIRE_AUTH=1      Demand HTTP digest credentials on every path,
 *                                 which exercises boxes with UPnP status transfer
 *                                 switched off.
 *   FRITZMOCK_WAN_UNCONFIGURED=1  Reproduce the box from issue #11: a PPPoE
 *                                 connection, so IGD WANIPConnection answers
 *                                 "Unconfigured" and only the TR-064
 *                                 WANPPPConnection service holds the WAN data.
 *
 * Like a real box, /igdupnp/control/WANPPPConn1 is answered with a 500 UPnPError:
 * FRITZ!OS lists no WANPPPConnection service in its IGD tree, and its control
 * endpoint reports unknown services as an error rather than 404 (issue #11).
 */

/** @var array<string, string> $responses */
$responses = require __DIR__ . '/fritzbox_soap_responses.php';

function upnpError(int $code, string $description): void {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/xml; charset="utf-8"');
    echo '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">'
        . '<s:Body><s:Fault>'
        . '<faultcode>s:Client</faultcode><faultstring>UPnPError</faultstring>'
        . '<detail><UPnPError xmlns="urn:schemas-upnp-org:control-1-0">'
        . '<errorCode>' . $code . '</errorCode><errorDescription>' . $description . '</errorDescription>'
        . '</UPnPError></detail>'
        . '</s:Fault></s:Body></s:Envelope>';
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$pppOnly = getenv('FRITZMOCK_WAN_UNCONFIGURED') === '1';

// Device description — unauthenticated on a real box, handy to confirm reachability.
if ($path === '/igddesc.xml') {
    header('Content-Type: text/xml; charset="utf-8"');
    echo '<?xml version="1.0"?>'
        . '<root xmlns="urn:schemas-upnp-org:device-1-0"><device>'
        . '<friendlyName>FRITZ!Box (mock)</friendlyName>'
        . '<manufacturer>AVM</manufacturer>'
        . '<modelName>FRITZ!Box 7590</modelName>'
        . '</device></root>';
    return;
}

// No FRITZ!OS release publishes WANPPPConnection in its IGD tree; the widget
// probes for it anyway, and has to survive the box saying no. A real box says
// it with a UPnPError, not a 404 (captured in issue #11).
if ($path === '/igdupnp/control/WANPPPConn1') {
    upnpError(401, 'Invalid Action');
    return;
}

$isTr064 = str_starts_with($path, '/upnp/control/');

// TR-064 always demands credentials, IGD only when status transfer is off.
// Auth is checked before the body: a digest client's bodyless first POST is
// answered 401, the way a real box answers it (issue #11).
if (($isTr064 || getenv('FRITZMOCK_REQUIRE_AUTH') === '1') && ($_SERVER['HTTP_AUTHORIZATION'] ?? '') === '') {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="F!Box SOAP-Auth", nonce="' . bin2hex(random_bytes(8)) . '"');
    return;
}

// Past auth, a control request without a SOAP envelope draws "XML error",
// exactly as real FRITZ!OS answers it. libcurl produces such a request when
// digest auth is attached to a POST up front — it strips the body while waiting
// for a 401 challenge that an unauthenticated endpoint never sends (issue #11).
if (str_contains($path, '/control/') && file_get_contents('php://input') === '') {
    upnpError(502, 'XML error');
    return;
}

// A real box selects the action from the SoapAction header, not from the path.
$soapAction = $_SERVER['HTTP_SOAPACTION'] ?? '';
$action = str_contains($soapAction, '#') ? substr($soapAction, strrpos($soapAction, '#') + 1) : '';

// A box whose connection runs over PPPoE reports nothing on WANIPConnection, and
// nothing on the DSL interface either once its internal modem is switched off.
if ($pppOnly && $action === 'GetStatusInfo') {
    $action = 'GetStatusInfoUnconfigured';
}
if ($pppOnly && $action === 'GetCommonLinkProperties') {
    $action = 'GetCommonLinkPropertiesUnlinked';
}
if ($pppOnly && $action === 'GetExternalIPAddress') {
    header('Content-Type: text/xml; charset="utf-8"');
    echo '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        . '<s:Body>'
        . '<u:GetExternalIPAddressResponse xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1">'
        . '<NewExternalIPAddress></NewExternalIPAddress>'
        . '</u:GetExternalIPAddressResponse>'
        . '</s:Body></s:Envelope>';
    return;
}

if (!isset($responses[$action])) {
    upnpError(401, 'Invalid Action');
    return;
}

header('Content-Type: text/xml; charset="utf-8"');
echo $responses[$action];
