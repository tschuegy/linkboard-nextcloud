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

// A box that lost its WAN connection reports 0.0.0.0 and no uptime. Without a
// status it stays at dashes — a silent box is a different problem than a box
// that answers.
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

// --- Boxes without a WAN connection of their own (issue #11) ---------------

$unconfigured = SoapResponseParser::toArray($fixtures['GetStatusInfoUnconfigured']);
expectSameValue('Unconfigured', $unconfigured['NewConnectionStatus'] ?? null, 'Unconfigured status not parsed');
expectSameValue('0', $unconfigured['NewUptime'] ?? null, 'Zero uptime not parsed');

$mapped = $widget->mapResponse([[], $unconfigured, []], []);
foreach (['externalIp', 'uptime', 'maxDown', 'maxUp'] as $field) {
    expectSameValue('—', $mapped[$field] ?? null, 'Field ' . $field . ' should be empty on an unconfigured box');
}
if (!str_contains($mapped['_warning'] ?? '', 'Unconfigured')) {
    throw new \RuntimeException('An unconfigured box does not explain its empty values');
}

// Every other state the box may report is named too, but only the documented ones.
expectSameValue(
    'The FRITZ!Box reports WAN status "Disconnecting", so the WAN values stay empty.',
    $widget->mapResponse([[], ['NewConnectionStatus' => 'Disconnecting'], []], [])['_warning'] ?? null,
    'A disconnecting box was not reported',
);
if (str_contains($widget->mapResponse([[], ['NewConnectionStatus' => '<b>x</b>'], []], [])['_warning'] ?? '', '<b>')) {
    throw new \RuntimeException('An unknown status was echoed back into the tile verbatim');
}

// --- PPPoE boxes: the WAN data sits on WANPPPConnection (issue #11) --------

// A box that answered WANIPConnection is not asked anything further.
expectSameValue(
    [],
    $widget->buildFollowUpRequests($parsed, 'http://192.168.178.1', []),
    'A connected box was probed for a PPP connection anyway',
);

// A real PPPoE box hands out the external IP and its configured tariff rates on
// the IGD tree even while calling WANIPConnection "Unconfigured" (issue #11) —
// present values must not stop the probes, only the status decides.
$realStageOne = [
    SoapResponseParser::toArray($fixtures['GetExternalIPAddress']),
    $unconfigured,
    SoapResponseParser::toArray($fixtures['GetCommonLinkPropertiesEthernet']),
];
expectSameValue(
    2,
    count($widget->buildFollowUpRequests($realStageOne, 'http://192.168.178.1', ['username' => 'lb', 'password' => 'secret'])),
    'A box reporting an IP next to "Unconfigured" was not probed for its PPP connection',
);

// A box that reports values but no status at all cannot be asked more precisely.
$silent = [SoapResponseParser::toArray($fixtures['GetExternalIPAddress']), [], []];
expectSameValue(
    [],
    $widget->buildFollowUpRequests($silent, 'http://192.168.178.1', ['password' => 'secret']),
    'A box without any status was probed although its values already showed',
);

// Without credentials only the IGD tree can be probed — TR-064 would answer 401.
$stageOne = [[], $unconfigured, []];
$probes = $widget->buildFollowUpRequests($stageOne, 'http://192.168.178.1', []);
expectSameValue(1, count($probes), 'An uncredentialed box was probed on TR-064');
expectSameValue('http://192.168.178.1:49000/igdupnp/control/WANPPPConn1', $probes[0]['url'], 'IGD PPP probe targets the wrong URL');

$probes = $widget->buildFollowUpRequests($stageOne, 'http://192.168.178.1', ['username' => 'lb', 'password' => 'secret']);
expectSameValue(2, count($probes), 'A credentialed box was not probed on TR-064');
$expectedProbes = [
    ['http://192.168.178.1:49000/igdupnp/control/WANPPPConn1', 'urn:schemas-upnp-org:service:WANPPPConnection:1#GetInfo'],
    ['http://192.168.178.1:49000/upnp/control/wanpppconn1', 'urn:dslforum-org:service:WANPPPConnection:1#GetInfo'],
];
foreach ($expectedProbes as $index => [$url, $soapAction]) {
    expectSameValue($url, $probes[$index]['url'], 'PPP probe ' . $index . ' targets the wrong URL');
    expectSameValue('xml', $probes[$index]['_response_format'], 'PPP probe ' . $index . ' is not parsed as XML');
    // A box that does not implement the service answers 404; that must not fail the tile.
    expectSameValue(true, $probes[$index]['_optional'], 'PPP probe ' . $index . ' is not optional');
    expectSameValue(['username' => 'lb', 'password' => 'secret'], $probes[$index]['_http_auth'], 'PPP probe ' . $index . ' lost its credentials');
    if (!in_array('SoapAction: ' . $soapAction, $probes[$index]['headers'], true)) {
        throw new \RuntimeException('PPP probe ' . $index . ' is missing SoapAction ' . $soapAction);
    }
    if (!str_contains($probes[$index]['body'], '<u:GetInfo xmlns:u="' . substr($soapAction, 0, strrpos($soapAction, '#')) . '"/>')) {
        throw new \RuntimeException('PPP probe ' . $index . ' envelope does not call GetInfo on its own service');
    }
}

// One GetInfo carries the whole connection, and it wins over the empty IGD
// answer. The fixture is a real box's reply (issue #11): its rates arrive in
// kbit/s — a 226/36 Mbit/s line — and must be scaled, not shown as 0.2 Mbps.
$ppp = SoapResponseParser::toArray($fixtures['GetInfo']);
expectSameValue(
    [
        'externalIp' => '84.130.12.34',
        'uptime' => '12h 21m',
        'maxDown' => '226.4 Mbps',
        'maxUp' => '36.2 Mbps',
    ],
    $widget->mapResponse([[], $unconfigured, [], [], $ppp], ['password' => 'secret']),
    'The PPP connection was not read once WANIPConnection came back empty',
);

// The same reply on the IGD probe would carry bit/s, which pass through unscaled.
$igdPpp = ['NewConnectionStatus' => 'Connected', 'NewUptime' => '44494',
    'NewExternalIPAddress' => '84.130.12.34',
    'NewDownstreamMaxBitRate' => '226415000', 'NewUpstreamMaxBitRate' => '36226000'];
expectSameValue('226.4 Mbps', $widget->mapResponse([[], $unconfigured, [], $igdPpp], [])['maxDown'],
    'The IGD PPP probe rates were scaled although IGD reports bit/s');

// The real box's full sequence (issue #11): the PPP sync rates beat the tariff
// rates the IGD tree reports, and the uptime comes from the PPP connection.
expectSameValue(
    [
        'externalIp' => '84.130.12.34',
        'uptime' => '12h 21m',
        'maxDown' => '226.4 Mbps',
        'maxUp' => '36.2 Mbps',
    ],
    $widget->mapResponse(array_merge($realStageOne, [[], $ppp]), ['password' => 'secret']),
    'The PPP connection did not win over the IGD tariff rates',
);

// The same box without credentials: what the IGD tree offers is shown, together
// with the advice that the rest sits behind TR-064.
$mapped = $widget->mapResponse(array_merge($realStageOne, [[]]), []);
expectSameValue('84.130.12.34', $mapped['externalIp'], 'The IGD external IP was withheld');
expectSameValue('250 Mbps', $mapped['maxDown'], 'The IGD tariff rate was withheld');
expectSameValue('—', $mapped['uptime'], 'An unknown uptime was invented');
if (!str_contains($mapped['_warning'] ?? '', 'PPPoE')) {
    throw new \RuntimeException('An uncredentialed PPPoE box showing IGD values lost its credentials advice');
}

// The physical line rate stands in where PPP negotiated none of its own.
$pppWithoutRates = ['NewConnectionStatus' => 'Connected', 'NewUptime' => '42', 'NewExternalIPAddress' => '84.130.12.34'];
$layer1 = SoapResponseParser::toArray($fixtures['GetCommonLinkProperties']);
$mapped = $widget->mapResponse([[], $unconfigured, $layer1, $pppWithoutRates], ['password' => 'secret']);
expectSameValue('249.9 Mbps', $mapped['maxDown'], 'Layer 1 downstream rate was not used as a fallback');
expectSameValue('49.8 Mbps', $mapped['maxUp'], 'Layer 1 upstream rate was not used as a fallback');
if (array_key_exists('_warning', $mapped)) {
    throw new \RuntimeException('A box connected over PPP was still flagged with a warning');
}

// A probe the box refused arrives as an empty response and changes nothing.
$mapped = $widget->mapResponse([[], $unconfigured, [], []], []);
expectSameValue('—', $mapped['uptime'], 'A refused probe was mistaken for data');
if (!str_contains($mapped['_warning'] ?? '', 'PPPoE')) {
    throw new \RuntimeException('An uncredentialed unconfigured box is not pointed at the credentials it needs');
}

// A TR-064 probe whose request itself failed is named in the tile — an empty
// answer and a rejected request need different fixes (issue #11).
$mapped = $widget->mapResponse([[], $unconfigured, [], [], ['_probe_failure' => 'HTTP 401']], ['password' => 'secret']);
expectSameValue('—', $mapped['uptime'], 'A failed probe was mistaken for data');
if (!str_contains($mapped['_warning'] ?? '', 'HTTP 401') || !str_contains($mapped['_warning'] ?? '', 'credentials')) {
    throw new \RuntimeException('A rejected TR-064 request does not name the 401 and the credentials');
}
if (!str_contains($widget->mapResponse([[], $unconfigured, [], [], ['_probe_failure' => 'cURL 28']], ['password' => 'secret'])['_warning'] ?? '', 'cURL 28')) {
    throw new \RuntimeException('A timed-out TR-064 request does not name the cURL error');
}
// The IGD probe fails on every real box — that is not worth a word in the tile.
if (str_contains($widget->mapResponse([[], $unconfigured, [], ['_probe_failure' => 'HTTP 500']], [])['_warning'] ?? '', 'HTTP 500')) {
    throw new \RuntimeException('The expected IGD probe failure was reported as a problem');
}

// Once credentials are configured, both interfaces have been asked — the advice changes.
$pppUnconfigured = SoapResponseParser::toArray($fixtures['GetInfoUnconfigured']);
$mapped = $widget->mapResponse([[], $unconfigured, [], [], $pppUnconfigured], ['password' => 'secret']);
expectSameValue('—', $mapped['externalIp'], 'A box without any WAN connection reported an address');
if (!str_contains($mapped['_warning'] ?? '', 'TR-064')) {
    throw new \RuntimeException('A credentialed box that answers nowhere does not say both interfaces were asked');
}

// A connected box must not carry a warning at all.
if (array_key_exists('_warning', $widget->mapResponse($parsed, []))) {
    throw new \RuntimeException('A connected box was flagged with a warning');
}

// Sub-day uptimes stay readable.
expectSameValue('3h 25m', $widget->mapResponse([[], ['NewUptime' => '12345'], []], [])['uptime'], 'Short uptime formatting is wrong');
expectSameValue('0m', $widget->mapResponse([[], ['NewUptime' => '42'], []], [])['uptime'], 'Sub-minute uptime formatting is wrong');

echo "Fritz!Box widget checks passed\n";
