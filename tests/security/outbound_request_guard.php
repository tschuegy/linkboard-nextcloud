<?php

declare(strict_types=1);

use OCA\LinkBoard\NotificationProvider\Providers\SmtpProvider;
use OCA\LinkBoard\Service\OutboundRequestGuard;
use OCA\LinkBoard\Widget\WebSocketJsonRpcClient;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// SPDX-License-Identifier: AGPL-3.0-or-later

function expectAllowed(OutboundRequestGuard $guard, string $url): void {
    $guard->resolveAllowed($url);
}

function expectRejected(callable $callback, string $description): void {
    try {
        $callback();
    } catch (\InvalidArgumentException) {
        return;
    }

    throw new \RuntimeException('Expected rejection: ' . $description);
}

$guard = new OutboundRequestGuard();

foreach ([
    'http://10.0.0.1',
    'http://192.168.1.1',
    'http://100.64.0.1',
    'http://[fc00::1]',
] as $url) {
    expectAllowed($guard, $url);
}

foreach ([
    'http://127.0.0.1',
    'http://169.254.169.254',
    'http://192.0.2.1',
    'http://224.0.0.1',
    'http://[::1]',
    'http://[::ffff:127.0.0.1]',
    'http://[fe80::1]',
    'http://[ff02::1]',
    'http://[2001:db8::1]',
] as $url) {
    expectRejected(
        fn() => $guard->resolveAllowed($url),
        $url,
    );
}

// Equivalent IPv6 spellings must compare by their packed address.
$guard->assertConnectedAddress('fc00::1', ['fc00:0:0:0:0:0:0:1']);
expectRejected(
    fn() => $guard->assertConnectedAddress('10.0.0.2', ['10.0.0.1']),
    'unexpected connected peer',
);

$smtpConfig = [
    'host' => '192.168.1.10',
    'port' => '22',
    'username' => 'linkboard',
    'password' => 'secret',
    'from' => 'linkboard@example.com',
    'to' => 'admin@example.com',
    'encryption' => 'tls',
];
expectRejected(
    fn() => (new SmtpProvider($guard))->send($smtpConfig, 'Test', 'Test'),
    'SMTP port outside the allowlist',
);

$smtpConfig['host'] = '127.0.0.1';
$smtpConfig['port'] = '25';
expectRejected(
    fn() => (new SmtpProvider($guard))->send($smtpConfig, 'Test', 'Test'),
    'SMTP loopback target',
);

expectRejected(
    fn() => (new WebSocketJsonRpcClient($guard))->execute('ws://127.0.0.1/', null, [], 1),
    'WebSocket loopback target',
);

echo "Outbound request security checks passed\n";
