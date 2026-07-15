<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

/** Validates user-controlled outbound HTTP(S) targets. */
class OutboundRequestGuard {
    public const MAX_RESPONSE_BYTES = 5 * 1024 * 1024;

    public function assertAllowed(string $url): void {
        $parts = parse_url($url);
        if (!is_array($parts)
            || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            throw new \InvalidArgumentException('Invalid outbound URL');
        }

        $host = trim((string)$parts['host'], '[]');
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);
        if ($addresses === []) {
            throw new \InvalidArgumentException('Outbound host cannot be resolved');
        }
        foreach ($addresses as $address) {
            if ($this->isForbiddenAddress($address)) {
                throw new \InvalidArgumentException('Outbound URL targets a forbidden address');
            }
        }
    }

    /** @return string[] */
    private function resolve(string $host): array {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (!is_array($records)) {
            return [];
        }
        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $addresses[] = $address;
            }
        }
        return array_values(array_unique($addresses));
    }

    private function isForbiddenAddress(string $address): bool {
        if (!filter_var($address, FILTER_VALIDATE_IP)) {
            return true;
        }
        // Private RFC1918/ULA ranges are intentional LinkBoard homelab targets.
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
        return str_starts_with($address, '127.')
            || $address === '::1'
            || str_starts_with(strtolower($address), 'fe80:')
            || str_starts_with($address, '169.254.');
    }
}
