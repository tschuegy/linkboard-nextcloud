<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget;

/**
 * Widget request failure carrying a bounded, credential-free reason.
 *
 * Only the upstream HTTP status or the cURL error number is kept — never URLs,
 * headers, credentials, or upstream response bodies.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class WidgetRequestException extends \RuntimeException {

    private function __construct(
        string $message,
        private ?int $httpStatus,
        private ?int $curlErrno,
    ) {
        parent::__construct($message);
    }

    public static function httpStatus(int $status): self {
        return new self('Widget HTTP request returned status ' . $status, $status, null);
    }

    public static function curlError(int $errno): self {
        return new self('Widget HTTP request failed with cURL error ' . $errno, null, $errno);
    }

    /**
     * Structured log context — integers only.
     *
     * @return array<string, int>
     */
    public function getLogContext(): array {
        if ($this->httpStatus !== null) {
            return ['httpStatus' => $this->httpStatus];
        }

        return $this->curlErrno !== null ? ['curlErrno' => $this->curlErrno] : [];
    }

    /**
     * Short technical detail safe to show the service owner, e.g. "HTTP 403".
     */
    public function getPublicDetail(): string {
        if ($this->httpStatus !== null) {
            return 'HTTP ' . $this->httpStatus;
        }

        return $this->curlErrno !== null ? 'cURL ' . $this->curlErrno : 'unknown';
    }
}
