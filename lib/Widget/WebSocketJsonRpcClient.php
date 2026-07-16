<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget;

use OCA\LinkBoard\Service\OutboundRequestGuard;

/**
 * Lightweight WebSocket JSON-RPC 2.0 client using native PHP streams.
 * No composer dependencies required.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class WebSocketJsonRpcClient {

    private OutboundRequestGuard $requestGuard;

    public function __construct(?OutboundRequestGuard $requestGuard = null) {
        $this->requestGuard = $requestGuard ?? new OutboundRequestGuard();
    }

    /**
     * Connect to a WebSocket endpoint, optionally authenticate, execute
     * JSON-RPC calls, and return an array of results (one per call).
     *
     * @param string     $wsUrl   WebSocket URL (https/http/wss/ws schemes accepted)
     * @param array|null $auth    Auth call spec: ['method' => '...', 'params' => [...]]
     * @param array      $calls   List of RPC calls: [['method' => '...', 'params' => [...]], ...]
     * @param int        $timeout Connection and read timeout in seconds
     * @return array              Array of result values, one per call
     */
    public function execute(string $wsUrl, ?array $auth, array $calls, int $timeout = 15, bool $verifyTls = true): array {
        $parsed = parse_url($wsUrl);
        $scheme = strtolower((string)($parsed['scheme'] ?? ''));
        if (!is_array($parsed)
            || !isset($parsed['host'])
            || !in_array($scheme, ['http', 'https', 'ws', 'wss'], true)) {
            throw new \RuntimeException('Invalid WebSocket URL');
        }

        $guardUrl = preg_replace('#^ws(s?):#i', 'http$1:', $wsUrl);
        if (!is_string($guardUrl)) {
            throw new \RuntimeException('Invalid WebSocket URL');
        }
        $target = $this->requestGuard->resolveAllowed($guardUrl);

        $useSsl = in_array($scheme, ['https', 'wss'], true);
        $host = $target['host'];
        $port = $target['port'];
        $path = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');

        $transport = $useSsl ? 'ssl' : 'tcp';
        $remoteAddress = $target['addresses'][0];
        $socketHost = str_contains($remoteAddress, ':') ? '[' . $remoteAddress . ']' : $remoteAddress;
        $address = $transport . '://' . $socketHost . ':' . $port;

        $context = stream_context_create(['ssl' => [
            'verify_peer' => $verifyTls,
            'verify_peer_name' => $verifyTls,
            'allow_self_signed' => !$verifyTls,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ]]);

        $socket = @stream_socket_client($address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new \RuntimeException('WebSocket connection failed');
        }

        try {
            $peerAddress = $this->extractPeerAddress(stream_socket_get_name($socket, true));
            $this->requestGuard->assertConnectedAddress($peerAddress, [$remoteAddress]);
            stream_set_timeout($socket, $timeout);
            $this->handshake($socket, $host, $port, $path);

            $rpcId = 0;

            // Authenticate if requested
            if ($auth) {
                $this->sendJsonRpc($socket, $rpcId, $auth['method'], $auth['params'] ?? []);
                $authResult = $this->readJsonRpcResult($socket, $rpcId);
                if ($authResult === false || $authResult === null) {
                    throw new \RuntimeException('WebSocket JSON-RPC auth failed');
                }
            }

            // Execute calls
            $results = [];
            foreach ($calls as $call) {
                $this->sendJsonRpc($socket, $rpcId, $call['method'], $call['params'] ?? []);
                $results[] = $this->readJsonRpcResult($socket, $rpcId);
            }

            $this->disconnect($socket);
            return $results;
        } catch (\Throwable $e) {
            @fclose($socket);
            throw $e;
        }
    }

    private function handshake($socket, string $host, int $port, string $path): void {
        $key = base64_encode(random_bytes(16));
        $headerHost = str_contains($host, ':') ? '[' . $host . ']' : $host;
        $hostHeader = $headerHost . ($port !== 443 && $port !== 80 ? ':' . $port : '');

        $request = "GET {$path} HTTP/1.1\r\n"
            . "Host: {$hostHeader}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: {$key}\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "\r\n";

        fwrite($socket, $request);

        $response = '';
        $responseBytes = 0;
        while (($line = fgets($socket)) !== false) {
            $response .= $line;
            $responseBytes += strlen($line);
            if ($responseBytes > 16384) {
                throw new \RuntimeException('WebSocket handshake response exceeds size limit');
            }
            if ($line === "\r\n") break;
        }

        $statusLine = strtok($response, "\r\n");
        if (!is_string($statusLine)
            || preg_match('/^HTTP\/1\.[01]\s+101(?:\s|$)/i', $statusLine) !== 1) {
            throw new \RuntimeException('WebSocket handshake failed');
        }

        $expectedAccept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if (preg_match('/^Sec-WebSocket-Accept:\s*(.*?)\s*$/mi', $response, $matches) !== 1
            || !hash_equals($expectedAccept, $matches[1])) {
            throw new \RuntimeException('WebSocket handshake validation failed');
        }
    }

    private function extractPeerAddress(string|false $peerName): string {
        if (!is_string($peerName) || $peerName === '') {
            throw new \RuntimeException('WebSocket peer address unavailable');
        }
        if ($peerName[0] === '[') {
            $closingBracket = strpos($peerName, ']');
            if ($closingBracket === false) {
                throw new \RuntimeException('Invalid WebSocket peer address');
            }
            return substr($peerName, 1, $closingBracket - 1);
        }

        $portSeparator = strrpos($peerName, ':');
        return $portSeparator === false ? $peerName : substr($peerName, 0, $portSeparator);
    }

    private function sendFrame($socket, string $payload): void {
        $len = strlen($payload);
        $frame = chr(0x81); // FIN + text opcode

        if ($len < 126) {
            $frame .= chr($len | 0x80); // masked
        } elseif ($len < 65536) {
            $frame .= chr(126 | 0x80) . pack('n', $len);
        } else {
            $frame .= chr(127 | 0x80) . pack('J', $len);
        }

        $mask = random_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $len; $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }

        $written = fwrite($socket, $frame);
        if ($written === false) {
            throw new \RuntimeException('WebSocket write failed');
        }
    }

    private function readFrame($socket): string {
        $header = $this->readExact($socket, 2);
        $opcode = ord($header[0]) & 0x0F;
        $masked = (ord($header[1]) & 0x80) !== 0;
        $len = ord($header[1]) & 0x7F;

        if ($len === 126) {
            $len = unpack('n', $this->readExact($socket, 2))[1];
        } elseif ($len === 127) {
            $len = unpack('J', $this->readExact($socket, 8))[1];
        }

        if ($len > OutboundRequestGuard::MAX_RESPONSE_BYTES) {
            throw new \RuntimeException('WebSocket frame exceeds size limit');
        }

        $mask = $masked ? $this->readExact($socket, 4) : null;
        $data = $len > 0 ? $this->readExact($socket, $len) : '';

        if ($mask) {
            for ($i = 0; $i < $len; $i++) {
                $data[$i] = $data[$i] ^ $mask[$i % 4];
            }
        }

        // Handle ping — respond with pong and read next frame
        if ($opcode === 0x9) {
            $this->sendPong($socket, $data);
            return $this->readFrame($socket);
        }

        // Handle close
        if ($opcode === 0x8) {
            throw new \RuntimeException('WebSocket closed by server');
        }

        return $data;
    }

    private function readExact($socket, int $length): string {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($socket);
                if (!empty($meta['timed_out'])) {
                    throw new \RuntimeException('WebSocket read timed out');
                }
                throw new \RuntimeException('WebSocket read failed');
            }
            $data .= $chunk;
        }
        return $data;
    }

    private function sendPong($socket, string $data): void {
        $len = strlen($data);
        $frame = chr(0x8A); // FIN + pong opcode
        $frame .= chr($len | 0x80);
        $mask = random_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $len; $i++) {
            $frame .= $data[$i] ^ $mask[$i % 4];
        }
        fwrite($socket, $frame);
    }

    private function sendJsonRpc($socket, int &$id, string $method, array $params): void {
        $id++;
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ], JSON_THROW_ON_ERROR);
        $this->sendFrame($socket, $payload);
    }

    private function readJsonRpcResult($socket, int $expectedId): mixed {
        $data = $this->readFrame($socket);
        $decoded = json_decode($data, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON-RPC response');
        }

        if (isset($decoded['error'])) {
            $msg = $decoded['error']['message'] ?? 'Unknown JSON-RPC error';
            throw new \RuntimeException('JSON-RPC error: ' . $msg);
        }

        if (($decoded['id'] ?? null) !== $expectedId) {
            throw new \RuntimeException('JSON-RPC response ID mismatch: expected ' . $expectedId . ', got ' . ($decoded['id'] ?? 'null'));
        }

        return $decoded['result'] ?? null;
    }

    private function disconnect($socket): void {
        // Send close frame
        $frame = chr(0x88) . chr(0x82); // FIN + close, masked, 2-byte payload
        $mask = random_bytes(4);
        $frame .= $mask;
        // Status code 1000 (normal closure)
        $payload = pack('n', 1000);
        for ($i = 0; $i < 2; $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }
        @fwrite($socket, $frame);
        @fclose($socket);
    }
}
