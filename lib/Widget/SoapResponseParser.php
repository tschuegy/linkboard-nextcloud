<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget;

use OCA\LinkBoard\Service\OutboundRequestGuard;

/**
 * Flattens a SOAP response envelope into the key/value pairs its action returned.
 *
 * Only the direct children of the single body element are read, which is all the
 * TR-064/UPnP actions LinkBoard calls ever return:
 *
 *     <s:Body><u:GetStatusInfoResponse><NewUptime>3600</NewUptime>…
 *     → ['NewUptime' => '3600', …]
 *
 * Anything unparsable yields an empty array — a malformed reply must never be
 * more than a widget without values.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
final class SoapResponseParser {

    private const SOAP_ENVELOPE_NS = 'http://schemas.xmlsoap.org/soap/envelope/';

    /** @return array<string, string> */
    public static function toArray(string $body): array {
        return self::parse($body) ?? [];
    }

    /**
     * Null when the body is not a readable SOAP envelope at all — as opposed to
     * an envelope whose action returned no values. The caller can then say "the
     * reply could not be parsed" instead of showing an unexplained empty tile
     * (issue #11).
     *
     * @return array<string, string>|null
     */
    public static function parse(string $body): ?array {
        $body = trim($body);
        if ($body === '' || strlen($body) > OutboundRequestGuard::MAX_RESPONSE_BYTES) {
            return null;
        }

        // A document type declaration has no place in a SOAP reply and is the entry
        // point for entity expansion and external entity attacks — refuse outright.
        if (stripos($body, '<!DOCTYPE') !== false) {
            return null;
        }

        $previousErrorHandling = libxml_use_internal_errors(true);
        try {
            $envelope = self::loadEnvelope($body);
            if ($envelope === null) {
                return null;
            }

            $envelope->registerXPathNamespace('s', self::SOAP_ENVELOPE_NS);
            $action = $envelope->xpath('/s:Envelope/s:Body/*');
            if (!is_array($action) || $action === []) {
                return null;
            }

            $values = [];
            // '*' matches element children in any namespace; the New* values AVM
            // returns carry no prefix while their parent action does.
            foreach ($action[0]->xpath('*') ?: [] as $value) {
                $values[$value->getName()] = trim((string)$value);
            }

            return $values;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorHandling);
        }
    }

    /**
     * FRITZ!OS emits its SOAP replies without an encoding declaration but with
     * ISO-8859-1 bytes in them — the character lists in a WANPPPConnection
     * GetInfo reply contain a section sign (issue #11). libxml assumes UTF-8
     * and rejects the whole document over that one byte, so a reply that is
     * not valid UTF-8 gets a second chance as Latin-1, a conversion that
     * cannot fail.
     */
    private static function loadEnvelope(string $body): ?\SimpleXMLElement {
        // Never LIBXML_NOENT: it is what turns entity substitution on.
        $envelope = simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        if ($envelope === false && !mb_check_encoding($body, 'UTF-8')) {
            $envelope = simplexml_load_string(
                mb_convert_encoding($body, 'UTF-8', 'ISO-8859-1'),
                \SimpleXMLElement::class,
                LIBXML_NONET | LIBXML_NOCDATA,
            );
        }

        return $envelope === false ? null : $envelope;
    }
}
