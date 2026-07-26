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
 * Set FRITZMOCK_REQUIRE_AUTH=1 to demand HTTP digest credentials, which exercises
 * the path for boxes with UPnP status transfer switched off.
 */

/** @var array<string, string> $responses */
$responses = require __DIR__ . '/fritzbox_soap_responses.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

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

if (getenv('FRITZMOCK_REQUIRE_AUTH') === '1' && ($_SERVER['HTTP_AUTHORIZATION'] ?? '') === '') {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="F!Box SOAP-Auth", nonce="' . bin2hex(random_bytes(8)) . '"');
    return;
}

// A real box selects the action from the SoapAction header, not from the path.
$soapAction = $_SERVER['HTTP_SOAPACTION'] ?? '';
$action = str_contains($soapAction, '#') ? substr($soapAction, strrpos($soapAction, '#') + 1) : '';

if (!isset($responses[$action])) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/xml; charset="utf-8"');
    echo '<?xml version="1.0"?>'
        . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">'
        . '<s:Body><s:Fault>'
        . '<faultcode>s:Client</faultcode><faultstring>UPnPError</faultstring>'
        . '<detail><UPnPError xmlns="urn:schemas-upnp-org:control-1-0">'
        . '<errorCode>401</errorCode><errorDescription>Invalid Action</errorDescription>'
        . '</UPnPError></detail>'
        . '</s:Fault></s:Body></s:Envelope>';
    return;
}

header('Content-Type: text/xml; charset="utf-8"');
echo $responses[$action];
