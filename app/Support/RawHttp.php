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
    public function get(string $url, int $timeout = 20): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            Log::error('RawHttp: unparseable URL', ['url' => $url]);

            return null;
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

            return null;
        }

        fwrite($socket, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n");

        $response = '';
        while (! feof($socket)) {
            $response .= fread($socket, 8192);
        }
        fclose($socket);

        // Tolerate malformed headers: split body off at the first blank line.
        $statusLine = strtok($response, "\r\n");
        if ($statusLine === false || ! str_contains($statusLine, ' 2')) {
            Log::error('RawHttp: non-2xx response', ['url' => $url, 'status' => $statusLine]);

            return null;
        }

        $bodyStart = strpos($response, "\r\n\r\n");

        return $bodyStart === false ? null : substr($response, $bodyStart + 4);
    }
}
