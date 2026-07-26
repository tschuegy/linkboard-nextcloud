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
        $body = trim($body);
        if ($body === '' || strlen($body) > OutboundRequestGuard::MAX_RESPONSE_BYTES) {
            return [];
        }

        // A document type declaration has no place in a SOAP reply and is the entry
        // point for entity expansion and external entity attacks — refuse outright.
        if (stripos($body, '<!DOCTYPE') !== false) {
            return [];
        }

        $previousErrorHandling = libxml_use_internal_errors(true);
        try {
            // Never LIBXML_NOENT: it is what turns entity substitution on.
            $envelope = simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if ($envelope === false) {
                return [];
            }

            $envelope->registerXPathNamespace('s', self::SOAP_ENVELOPE_NS);
            $action = $envelope->xpath('/s:Envelope/s:Body/*');
            if (!is_array($action) || $action === []) {
                return [];
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
}
