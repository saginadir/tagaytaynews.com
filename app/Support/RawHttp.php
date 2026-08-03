<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Minimal raw-socket HTTP GET for servers with malformed response headers
 * (PHIVOLCS sends "space in header name", which breaks curl/Guzzle/streams).
 * Speaks HTTP/1.0 with Connection: close and returns the body unparsed.
 */
class RawHttp
{
    public function get(string $url, int $timeout = 20, int $redirects = 3): ?string
    {
        while (true) {
            [$statusLine, $headers, $body] = $this->fetch($url, $timeout);

            if ($statusLine === null) {
                return null;
            }

            // Follow redirects manually — Location header names are unreliable,
            // so match case-insensitively on raw header text.
            if (preg_match('/\s3\d\d/', $statusLine) && $redirects > 0) {
                if (preg_match('/^location:\s*(\S+)/im', $headers, $match)) {
                    $url = $this->resolve($url, trim($match[1]));
                    $redirects--;

                    continue;
                }
            }

            if (! str_contains($statusLine, ' 2')) {
                Log::error('RawHttp: non-2xx response', ['url' => $url, 'status' => $statusLine]);

                return null;
            }

            // Decompress when the server gzips (sniff the gzip magic bytes —
            // header names are unreliable on this class of servers).
            if (str_starts_with($body, "\x1f\x8b")) {
                $decoded = @gzdecode($body);

                if ($decoded === false) {
                    Log::error('RawHttp: gzip decode failed', ['url' => $url]);

                    return null;
                }

                return $decoded;
            }

            return $body;
        }
    }

    /**
     * @return array{0: ?string, 1: string, 2: string} status line, raw headers, body
     */
    private function fetch(string $url, int $timeout): array
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            Log::error('RawHttp: unparseable URL', ['url' => $url]);

            return [null, '', ''];
        }

        $host = $parts['host'];
        $scheme = $parts['scheme'] ?? 'https';
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        $remote = ($scheme === 'https' ? 'tls://' : 'tcp://').$host.':'.$port;

        $context = stream_context_create([
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if ($socket === false) {
            Log::error('RawHttp: connect failed', ['url' => $url, 'error' => $errstr]);

            return [null, '', ''];
        }

        fwrite($socket, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n");

        $response = '';
        while (! feof($socket)) {
            $response .= fread($socket, 8192);
        }
        fclose($socket);

        $statusLine = strtok($response, "\r\n") ?: null;
        $bodyStart = strpos($response, "\r\n\r\n");

        return [
            $statusLine === false ? null : $statusLine,
            $bodyStart === false ? '' : substr($response, 0, $bodyStart),
            $bodyStart === false ? '' : substr($response, $bodyStart + 4),
        ];
    }

    private function resolve(string $base, string $location): string
    {
        if (str_starts_with($location, 'http')) {
            return $location;
        }

        $parts = parse_url($base);
        $root = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        return str_starts_with($location, '/')
            ? $root.$location
            : $root.rtrim(dirname($parts['path'] ?? '/'), '/').'/'.$location;
    }
}
