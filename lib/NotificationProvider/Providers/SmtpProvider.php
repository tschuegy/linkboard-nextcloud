<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider\Providers;

use OCA\LinkBoard\NotificationProvider\AbstractNotificationProvider;

class SmtpProvider extends AbstractNotificationProvider {

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
        $encryption = $config['encryption'] ?? 'tls';
        $port = (int)($config['port'] ?? 587);

        $prefix = $encryption === 'ssl' ? 'ssl://' : ($encryption === 'tls' ? 'tls://' : '');
        $socket = @fsockopen($prefix . $config['host'], $port, $errno, $errstr, 10);
        if (!$socket) {
            throw new \RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
        }

        $this->smtpRead($socket);
        $this->smtpWrite($socket, "EHLO LinkBoard\r\n");
        $this->smtpRead($socket);

        // STARTTLS for port 587 without ssl:// prefix
        if ($encryption === 'tls' && !str_starts_with($prefix, 'ssl://') && !str_starts_with($prefix, 'tls://')) {
            $this->smtpWrite($socket, "STARTTLS\r\n");
            $this->smtpRead($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            $this->smtpWrite($socket, "EHLO LinkBoard\r\n");
            $this->smtpRead($socket);
        }

        // AUTH LOGIN
        $this->smtpWrite($socket, "AUTH LOGIN\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, base64_encode($config['username']) . "\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, base64_encode($config['password']) . "\r\n");
        $resp = $this->smtpRead($socket);
        if (!str_starts_with($resp, '235')) {
            fclose($socket);
            throw new \RuntimeException('SMTP authentication failed');
        }

        $this->smtpWrite($socket, "MAIL FROM:<{$config['from']}>\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, "RCPT TO:<{$config['to']}>\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, "DATA\r\n");
        $this->smtpRead($socket);

        $date = date('r');
        $body = "From: {$config['from']}\r\nTo: {$config['to']}\r\nSubject: {$title}\r\nDate: {$date}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$message}\r\n.\r\n";
        $this->smtpWrite($socket, $body);
        $this->smtpRead($socket);

        $this->smtpWrite($socket, "QUIT\r\n");
        fclose($socket);
    }

    private function smtpWrite($socket, string $data): void {
        fwrite($socket, $data);
    }

    private function smtpRead($socket): string {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    }
}
