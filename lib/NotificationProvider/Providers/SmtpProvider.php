<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;
use OCA\LinkBoard\Service\OutboundRequestGuard;

class SmtpProvider extends AbstractNotificationProvider {

    private const ALLOWED_PORTS = [25, 465, 587, 1025, 2525];
    private const IO_TIMEOUT_SECONDS = 10;
    private const MAX_RESPONSE_BYTES = 65536;

    private OutboundRequestGuard $requestGuard;

    public function __construct(?OutboundRequestGuard $requestGuard = null) {
        $this->requestGuard = $requestGuard ?? new OutboundRequestGuard();
    }

    public function getId(): string { return 'smtp'; }
    public function getLabel(): string { return 'E-Mail (SMTP)'; }
    public function getCategory(): string { return 'email'; }

    public function getConfigFields(): array {
        return [
            ['key' => 'host', 'label' => 'SMTP host', 'type' => 'text', 'required' => true, 'placeholder' => 'smtp.example.com'],
            ['key' => 'port', 'label' => 'Port', 'type' => 'text', 'required' => true, 'placeholder' => '587'],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
            ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
            ['key' => 'from', 'label' => 'From address', 'type' => 'text', 'required' => true, 'placeholder' => 'linkboard@example.com'],
            ['key' => 'to', 'label' => 'To address', 'type' => 'text', 'required' => true],
            ['key' => 'encryption', 'label' => 'Encryption (tls/ssl/none)', 'type' => 'text', 'required' => false, 'placeholder' => 'tls'],
        ];
    }

    public function send(array $config, string $title, string $message): void {
        $host = trim((string)($config['host'] ?? ''));
        $portValue = (string)($config['port'] ?? '587');
        $encryption = strtolower(trim((string)($config['encryption'] ?? 'tls')));
        $username = (string)($config['username'] ?? '');
        $password = (string)($config['password'] ?? '');
        $from = trim((string)($config['from'] ?? ''));
        $to = trim((string)($config['to'] ?? ''));

        if (!ctype_digit($portValue)
            || !in_array((int)$portValue, self::ALLOWED_PORTS, true)
            || !in_array($encryption, ['tls', 'ssl', 'none'], true)
            || $username === ''
            || $password === ''
            || filter_var($from, FILTER_VALIDATE_EMAIL) === false
            || filter_var($to, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/[\r\n]/', $from . $to) === 1) {
            throw new \InvalidArgumentException('Invalid SMTP configuration');
        }
        $port = (int)$portValue;

        $target = $this->requestGuard->resolveHost($host, $port);
        $remoteAddress = $target['addresses'][0];
        $socketHost = str_contains($remoteAddress, ':') ? '[' . $remoteAddress . ']' : $remoteAddress;
        $transport = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $context = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'peer_name' => $target['host'],
            'SNI_enabled' => true,
        ]]);

        $socket = @stream_socket_client(
            $transport . '://' . $socketHost . ':' . $port,
            $errno,
            $errstr,
            self::IO_TIMEOUT_SECONDS,
            STREAM_CLIENT_CONNECT,
            $context,
        );
        if ($socket === false) {
            throw new \RuntimeException('SMTP connection failed');
        }

        try {
            $peerAddress = $this->extractPeerAddress(stream_socket_get_name($socket, true));
            $this->requestGuard->assertConnectedAddress($peerAddress, [$remoteAddress]);
            stream_set_timeout($socket, self::IO_TIMEOUT_SECONDS);

            $this->smtpExpect($socket, 220);
            $this->smtpWrite($socket, "EHLO LinkBoard\r\n");
            $this->smtpExpect($socket, 250);

            if ($encryption === 'tls') {
                $this->smtpWrite($socket, "STARTTLS\r\n");
                $this->smtpExpect($socket, 220);
                $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoEnabled !== true) {
                    throw new \RuntimeException('SMTP TLS negotiation failed');
                }
                $this->smtpWrite($socket, "EHLO LinkBoard\r\n");
                $this->smtpExpect($socket, 250);
            }

            $this->smtpWrite($socket, "AUTH LOGIN\r\n");
            $this->smtpExpect($socket, 334);
            $this->smtpWrite($socket, base64_encode($username) . "\r\n");
            $this->smtpExpect($socket, 334);
            $this->smtpWrite($socket, base64_encode($password) . "\r\n");
            $this->smtpExpect($socket, 235);

            $this->smtpWrite($socket, "MAIL FROM:<{$from}>\r\n");
            $this->smtpExpect($socket, 250);
            $this->smtpWrite($socket, "RCPT TO:<{$to}>\r\n");
            $this->smtpExpect($socket, 250, 251);
            $this->smtpWrite($socket, "DATA\r\n");
            $this->smtpExpect($socket, 354);

            $subject = '=?UTF-8?B?' . base64_encode(substr($title, 0, 512)) . '?=';
            $normalizedMessage = str_replace(["\r\n", "\r"], "\n", $message);
            $normalizedMessage = str_replace("\n", "\r\n", $normalizedMessage);
            $dotStuffedMessage = preg_replace('/^\./m', '..', $normalizedMessage) ?? $normalizedMessage;
            $date = date('r');
            $body = "From: {$from}\r\n"
                . "To: {$to}\r\n"
                . "Subject: {$subject}\r\n"
                . "Date: {$date}\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n"
                . "\r\n"
                . rtrim($dotStuffedMessage, "\r\n")
                . "\r\n.\r\n";
            $this->smtpWrite($socket, $body);
            $this->smtpExpect($socket, 250);

            $this->smtpWrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    private function smtpWrite($socket, string $data): void {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                $meta = stream_get_meta_data($socket);
                if (!empty($meta['timed_out'])) {
                    throw new \RuntimeException('SMTP write timed out');
                }
                throw new \RuntimeException('SMTP write failed');
            }
            $offset += $written;
        }
    }

    private function smtpRead($socket): string {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($response) > self::MAX_RESPONSE_BYTES) {
                throw new \RuntimeException('SMTP response exceeds size limit');
            }
            if (isset($line[3]) && $line[3] === ' ') {
                return $response;
            }
        }

        $meta = stream_get_meta_data($socket);
        if (!empty($meta['timed_out'])) {
            throw new \RuntimeException('SMTP read timed out');
        }
        throw new \RuntimeException('SMTP connection closed unexpectedly');
    }

    private function smtpExpect($socket, int ...$expectedCodes): string {
        $response = $this->smtpRead($socket);
        $responseCode = (int)substr($response, 0, 3);
        if (!in_array($responseCode, $expectedCodes, true)) {
            throw new \RuntimeException('Unexpected SMTP response code ' . $responseCode);
        }
        return $response;
    }

    private function extractPeerAddress(string|false $peerName): string {
        if (!is_string($peerName) || $peerName === '') {
            throw new \RuntimeException('SMTP peer address unavailable');
        }
        if ($peerName[0] === '[') {
            $closingBracket = strpos($peerName, ']');
            if ($closingBracket === false) {
                throw new \RuntimeException('Invalid SMTP peer address');
            }
            return substr($peerName, 1, $closingBracket - 1);
        }

        $portSeparator = strrpos($peerName, ':');
        return $portSeparator === false ? $peerName : substr($peerName, 0, $portSeparator);
    }
}
