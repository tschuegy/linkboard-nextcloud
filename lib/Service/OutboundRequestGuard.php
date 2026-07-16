<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

/** Validates user-controlled outbound HTTP(S) targets. */
class OutboundRequestGuard {
    public const MAX_RESPONSE_BYTES = 5 * 1024 * 1024;

    private const MAX_RESOLVED_ADDRESSES = 16;

    private const FORBIDDEN_CIDRS = [
        '0.0.0.0/8',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '::/128',
        '::1/128',
        '::/96',
        '100::/64',
        '2001:db8::/32',
        'fe80::/10',
        'fec0::/10',
        'ff00::/8',
    ];

    public function assertAllowed(string $url): void {
        $this->resolveAllowed($url);
    }

    /**
     * Resolve and validate an outbound URL.
     *
     * @return array{host: string, port: int, addresses: list<string>}
     */
    public function resolveAllowed(string $url): array {
        $parts = parse_url($url);
        if (!is_array($parts)
            || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            throw new \InvalidArgumentException('Invalid outbound URL');
        }

        $scheme = strtolower((string)$parts['scheme']);
        $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);

        return $this->resolveHost((string)$parts['host'], $port);
    }

    /**
     * Resolve and validate a host and port for non-HTTP transports.
     *
     * @return array{host: string, port: int, addresses: list<string>}
     */
    public function resolveHost(string $host, int $port): array {
        $host = trim($host);
        if ($port < 1 || $port > 65535
            || $host === ''
            || preg_match('/[\x00-\x20\x7f\/\\\\@]/', $host) === 1) {
            throw new \InvalidArgumentException('Invalid outbound URL');
        }

        $host = trim($host, '[]');
        $isIpAddress = filter_var($host, FILTER_VALIDATE_IP) !== false;
        if (!$isIpAddress && preg_match('/^[a-z0-9.-]+$/iD', $host) !== 1) {
            throw new \InvalidArgumentException('Invalid outbound URL');
        }

        $addresses = $isIpAddress ? [$host] : $this->resolve($host);
        if ($addresses === []) {
            throw new \InvalidArgumentException('Outbound host cannot be resolved');
        }

        foreach ($addresses as $address) {
            $this->assertConnectedAddress($address);
        }

        return [
            'host' => $host,
            'port' => $port,
            'addresses' => $addresses,
        ];
    }

    /**
     * Pin cURL to the addresses that were validated above.
     *
     * @return array{host: string, port: int, addresses: list<string>}
     */
    public function pinCurl(\CurlHandle $handle, string $url): array {
        $target = $this->resolveAllowed($url);
        if (filter_var($target['host'], FILTER_VALIDATE_IP) === false) {
            $addresses = array_map(
                fn(string $address): string => str_contains($address, ':') ? '[' . $address . ']' : $address,
                $target['addresses'],
            );
            $entry = $target['host'] . ':' . $target['port'] . ':' . implode(',', $addresses);
            if (!curl_setopt($handle, CURLOPT_RESOLVE, [$entry])) {
                throw new \RuntimeException('Unable to pin outbound host');
            }
        }

        return $target;
    }

    /**
     * Ensure cURL connected to one of the addresses that was originally checked.
     *
     * @param list<string> $expectedAddresses
     */
    public function assertCurlConnection(\CurlHandle $handle, array $expectedAddresses): void {
        $primaryAddress = curl_getinfo($handle, CURLINFO_PRIMARY_IP);
        if ($primaryAddress !== '') {
            $this->assertConnectedAddress($primaryAddress, $expectedAddresses);
        }
    }

    /**
     * Validate a concrete peer address and optionally require it to be part of
     * the DNS result that was checked before connecting.
     *
     * @param list<string> $expectedAddresses
     */
    public function assertConnectedAddress(string $address, array $expectedAddresses = []): void {
        if ($this->isForbiddenAddress($address)) {
            throw new \InvalidArgumentException('Outbound URL targets a forbidden address');
        }

        if ($expectedAddresses !== [] && !$this->addressIsExpected($address, $expectedAddresses)) {
            throw new \InvalidArgumentException('Outbound connection used an unexpected address');
        }
    }

    /** @return list<string> */
    private function resolve(string $host): array {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (!is_array($records)) {
            return [];
        }

        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $packed = @inet_pton($address);
                if ($packed !== false) {
                    $normalized = inet_ntop($packed);
                    if (is_string($normalized)) {
                        $addresses[] = $normalized;
                    }
                }
            }
            if (count($addresses) >= self::MAX_RESOLVED_ADDRESSES) {
                break;
            }
        }

        return array_values(array_unique($addresses));
    }

    private function isForbiddenAddress(string $address): bool {
        if (!filter_var($address, FILTER_VALIDATE_IP)) {
            return true;
        }

        $packed = @inet_pton($address);
        if ($packed === false) {
            return true;
        }

        // Apply the IPv4 policy to IPv4-mapped IPv6 addresses as well.
        if (strlen($packed) === 16
            && substr($packed, 0, 10) === str_repeat("\0", 10)
            && substr($packed, 10, 2) === "\xff\xff") {
            $mapped = inet_ntop(substr($packed, 12));
            return !is_string($mapped) || $this->isForbiddenAddress($mapped);
        }

        // Private RFC1918/ULA ranges are intentional LinkBoard homelab targets.
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        foreach (self::FORBIDDEN_CIDRS as $cidr) {
            if ($this->isInCidr($address, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $expectedAddresses */
    private function addressIsExpected(string $address, array $expectedAddresses): bool {
        $packedAddress = @inet_pton($address);
        if ($packedAddress === false) {
            return false;
        }

        foreach ($expectedAddresses as $expectedAddress) {
            $packedExpected = @inet_pton($expectedAddress);
            if ($packedExpected !== false && hash_equals($packedExpected, $packedAddress)) {
                return true;
            }
        }

        return false;
    }

    private function isInCidr(string $address, string $cidr): bool {
        [$network, $prefixLength] = explode('/', $cidr, 2);
        $packedAddress = @inet_pton($address);
        $packedNetwork = @inet_pton($network);
        if ($packedAddress === false
            || $packedNetwork === false
            || strlen($packedAddress) !== strlen($packedNetwork)) {
            return false;
        }

        $bits = (int)$prefixLength;
        $maxBits = strlen($packedAddress) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        if ($fullBytes > 0
            && substr($packedAddress, 0, $fullBytes) !== substr($packedNetwork, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $bits % 8;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;
        return (ord($packedAddress[$fullBytes]) & $mask)
            === (ord($packedNetwork[$fullBytes]) & $mask);
    }
}
