<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider;

use OCA\LinkBoard\Service\OutboundRequestGuard;

/**
 * Base class for all LinkBoard notification providers.
 *
 * Each provider knows how to send alert messages to an external
 * notification service (chat, push, email, webhook).
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
abstract class AbstractNotificationProvider {

    abstract public function getId(): string;
    abstract public function getLabel(): string;
    abstract public function getCategory(): string; // 'universal'|'chat'|'push'|'email'

    /**
     * @return array<array{key: string, label: string, type: string, required: bool, placeholder?: string}>
     */
    abstract public function getConfigFields(): array;

    /**
     * Send a notification message.
     *
     * @throws \RuntimeException on failure
     */
    abstract public function send(array $config, string $title, string $message): void;

    /**
     * Perform a cURL request (SSL verify off, 15s timeout — homelab pattern).
     *
     * @return array{httpCode: int, body: string, error: string}
     */
    protected function curlRequest(string $url, string $method, array $headers, ?string $body): array {
        $requestGuard = new OutboundRequestGuard();
        $ch = curl_init();
        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_MAXFILESIZE => OutboundRequestGuard::MAX_RESPONSE_BYTES,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'LinkBoard/1.0 Notification',
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $target = $requestGuard->pinCurl($ch, $url);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $responseBody = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $requestGuard->assertCurlConnection($ch, $target['addresses']);
            $error = curl_error($ch);
        } finally {
            curl_close($ch);
        }

        return [
            'httpCode' => $httpCode,
            'body' => is_string($responseBody) ? $responseBody : '',
            'error' => $error,
        ];
    }

    /**
     * Helper: perform a JSON POST request.
     */
    protected function jsonPost(string $url, array $payload, array $extraHeaders = []): array {
        $headers = array_merge(['Content-Type: application/json'], $extraHeaders);
        return $this->curlRequest($url, 'POST', $headers, json_encode($payload));
    }

    /**
     * Helper: perform a form-encoded POST request.
     */
    protected function formPost(string $url, array $payload, array $extraHeaders = []): array {
        $headers = array_merge(['Content-Type: application/x-www-form-urlencoded'], $extraHeaders);
        return $this->curlRequest($url, 'POST', $headers, http_build_query($payload));
    }

    /**
     * Assert that the HTTP response was successful.
     *
     * @throws \RuntimeException
     */
    protected function assertSuccess(array $result, int $maxCode = 299): void {
        if (!empty($result['error'])) {
            throw new \RuntimeException('Connection error: ' . $result['error']);
        }
        if ($result['httpCode'] < 200 || $result['httpCode'] > $maxCode) {
            throw new \RuntimeException('HTTP ' . $result['httpCode'] . ': ' . substr($result['body'], 0, 200));
        }
    }

    public function toCatalog(): array {
        return [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'category' => $this->getCategory(),
            'configFields' => $this->getConfigFields(),
        ];
    }
}
