<?php

declare(strict_types=1);

use OCA\LinkBoard\Widget\SoapResponseParser;
use OCA\LinkBoard\Widget\Widgets\FritzboxWidget;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// SPDX-License-Identifier: AGPL-3.0-or-later

function expectSameValue(mixed $expected, mixed $actual, string $description): void {
    if ($expected !== $actual) {
        throw new \RuntimeException($description . ' (expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true) . ')');
    }
}

/** @var array<string, string> $fixtures */
$fixtures = require dirname(__DIR__) . '/fixtures/fritzbox_soap_responses.php';

$widget = new FritzboxWidget();

// --- Requests -------------------------------------------------------------

$requests = $widget->buildRequests('http://192.168.178.1', []);
expectSameValue(3, count($requests), 'Unexpected number of SOAP requests');

$expectedRequests = [
    ['http://192.168.178.1:49000/igdupnp/control/WANIPConn1', 'urn:schemas-upnp-org:service:WANIPConnection:1#GetExternalIPAddress'],
    ['http://192.168.178.1:49000/igdupnp/control/WANIPConn1', 'urn:schemas-upnp-org:service:WANIPConnection:1#GetStatusInfo'],
    ['http://192.168.178.1:49000/igdupnp/control/WANCommonIFC1', 'urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1#GetCommonLinkProperties'],
];
foreach ($expectedRequests as $index => [$url, $soapAction]) {
    expectSameValue($url, $requests[$index]['url'], 'Request ' . $index . ' targets the wrong URL');
    expectSameValue('POST', $requests[$index]['method'], 'Request ' . $index . ' is not a POST');
    expectSameValue('xml', $requests[$index]['_response_format'], 'Request ' . $index . ' is not parsed as XML');
    if (!in_array('SoapAction: ' . $soapAction, $requests[$index]['headers'], true)) {
        throw new \RuntimeException('Request ' . $index . ' is missing SoapAction ' . $soapAction);
    }
    // The action element in the envelope must match the header.
    $action = substr($soapAction, strrpos($soapAction, '#') + 1);
    if (!str_contains($requests[$index]['body'], '<u:' . $action . ' ')) {
        throw new \RuntimeException('Request ' . $index . ' envelope does not call ' . $action);
    }
}

// Credentials ride along only when configured; cURL holds them back until challenged.
expectSameValue(['username' => '', 'password' => ''], $requests[0]['_http_auth'], 'Empty credentials were not passed through');
$authenticated = $widget->buildRequests('http://192.168.178.1', ['username' => 'admin', 'password' => 'secret']);
expectSameValue(['username' => 'admin', 'password' => 'secret'], $authenticated[0]['_http_auth'], 'Configured credentials were dropped');

// --- Endpoint normalization ----------------------------------------------

$endpoints = [
    'http://192.168.178.1' => 'http://192.168.178.1:49000/igdupnp/control/WANIPConn1',
    'http://192.168.178.1/' => 'http://192.168.178.1:49000/igdupnp/control/WANIPConn1',
    'http://fritz.box/admin/' => 'http://fritz.box:49000/igdupnp/control/WANIPConn1',
    // An explicit port wins — the user may have forwarded TR-064 elsewhere.
    'http://192.168.178.1:49123' => 'http://192.168.178.1:49123/igdupnp/control/WANIPConn1',
    // HTTPS falls back to plain TR-064: port 49443 serves a self-signed certificate.
    'https://fritz.box' => 'http://fritz.box:49000/igdupnp/control/WANIPConn1',
    // A bare host without a scheme still has to resolve to something usable.
    '192.168.178.1' => 'http://192.168.178.1:49000/igdupnp/control/WANIPConn1',
];
foreach ($endpoints as $serviceUrl => $expected) {
    expectSameValue($expected, $widget->buildRequests($serviceUrl, [])[0]['url'], 'Normalization failed for ' . $serviceUrl);
}

// --- Response parsing -----------------------------------------------------

$parsed = array_map(
    static fn(string $xml): array => SoapResponseParser::toArray($xml),
    [$fixtures['GetExternalIPAddress'], $fixtures['GetStatusInfo'], $fixtures['GetCommonLinkProperties']],
);

expectSameValue('84.130.12.34', $parsed[0]['NewExternalIPAddress'] ?? null, 'External IP not parsed');
expectSameValue('1234567', $parsed[1]['NewUptime'] ?? null, 'Uptime not parsed');
expectSameValue('Connected', $parsed[1]['NewConnectionStatus'] ?? null, 'Connection status not parsed');
expectSameValue('249856000', $parsed[2]['NewLayer1DownstreamMaxBitRate'] ?? null, 'Downstream rate not parsed');
expectSameValue('49792000', $parsed[2]['NewLayer1UpstreamMaxBitRate'] ?? null, 'Upstream rate not parsed');

// Malformed input must degrade to an empty result, never to an exception.
expectSameValue([], SoapResponseParser::toArray(''), 'Empty body was not rejected');
expectSameValue([], SoapResponseParser::toArray('{"json":true}'), 'Non-XML body was not rejected');
expectSameValue([], SoapResponseParser::toArray('<s:Envelope><broken'), 'Malformed XML was not rejected');

// Entity expansion is refused before the parser ever sees the document.
$billionLaughs = '<?xml version="1.0"?><!DOCTYPE lolz [<!ENTITY lol "lol">'
    . '<!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">]>'
    . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body>'
    . '<u:GetStatusInfoResponse xmlns:u="urn:x"><NewUptime>&lol2;</NewUptime></u:GetStatusInfoResponse>'
    . '</s:Body></s:Envelope>';
expectSameValue([], SoapResponseParser::toArray($billionLaughs), 'A DOCTYPE declaration was accepted');

// --- Mapped values --------------------------------------------------------

expectSameValue(
    [
        'externalIp' => '84.130.12.34',
        'uptime' => '14d 6h',
        'maxDown' => '249.9 Mbps',
        'maxUp' => '49.8 Mbps',
    ],
    $widget->mapResponse($parsed, []),
    'Mapped widget values do not match the fixtures',
);

// A box that lost its WAN connection reports 0.0.0.0 and no uptime.
expectSameValue(
    [
        'externalIp' => '—',
        'uptime' => '—',
        'maxDown' => '—',
        'maxUp' => '—',
    ],
    $widget->mapResponse([['NewExternalIPAddress' => '0.0.0.0'], ['NewUptime' => '0'], []], []),
    'Disconnected box was not reported as unavailable',
);

// Sub-day uptimes stay readable.
expectSameValue('3h 25m', $widget->mapResponse([[], ['NewUptime' => '12345'], []], [])['uptime'], 'Short uptime formatting is wrong');
expectSameValue('0m', $widget->mapResponse([[], ['NewUptime' => '42'], []], [])['uptime'], 'Sub-minute uptime formatting is wrong');

echo "Fritz!Box widget checks passed\n";
