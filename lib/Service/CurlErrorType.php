<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

/**
 * Maps cURL error numbers to a small set of stable, user-explainable types.
 *
 * The frontend translates these types into actionable hints (e.g. a
 * self-signed certificate on a homelab service), so the raw cURL message
 * never has to be shown on its own.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
final class CurlErrorType {
    public const TLS_CERTIFICATE = 'tls_certificate';
    public const TLS_HANDSHAKE = 'tls_handshake';
    public const TIMEOUT = 'timeout';
    public const CONNECT = 'connect';
    public const DNS = 'dns';

    /** cURL errors caused by a certificate that could not be verified. */
    private const TLS_CERTIFICATE_ERRNOS = [
        51, // CURLE_PEER_FAILED_VERIFICATION (older libcurl)
        60, // CURLE_PEER_FAILED_VERIFICATION / CURLE_SSL_CACERT
        83, // CURLE_SSL_ISSUER_ERROR
        90, // CURLE_SSL_PINNEDPUBKEYNOTMATCH
        91, // CURLE_SSL_INVALIDCERTSTATUS
    ];

    /** Other TLS failures (handshake, protocol, local TLS setup). */
    private const TLS_HANDSHAKE_ERRNOS = [35, 53, 54, 58, 59, 64, 66, 77, 80, 82];

    public static function fromErrno(int $errno): ?string {
        if (in_array($errno, self::TLS_CERTIFICATE_ERRNOS, true)) {
            return self::TLS_CERTIFICATE;
        }
        if (in_array($errno, self::TLS_HANDSHAKE_ERRNOS, true)) {
            return self::TLS_HANDSHAKE;
        }

        return match ($errno) {
            28 => self::TIMEOUT,
            7 => self::CONNECT,
            6 => self::DNS,
            default => null,
        };
    }
}
