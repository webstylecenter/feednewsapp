<?php

declare(strict_types=1);

namespace App\Shared\Http;

use RuntimeException;

final readonly class NativeHttpClient
{
    private const int DEFAULT_MAX_BYTES = 2_000_000;
    private const int MAX_REDIRECTS = 5;

    public function __construct(private PublicUrlGuard $urlGuard)
    {
    }

    /**
     * @param array<string, string> $headers
     */
    public function get(string $url, array $headers = [], int $maxBytes = self::DEFAULT_MAX_BYTES): HttpResponse
    {
        return $this->request('GET', $url, $headers, null, $maxBytes);
    }

    /**
     * @param array<string, string> $headers
     */
    public function head(string $url, array $headers = []): HttpResponse
    {
        return $this->request('HEAD', $url, $headers, null, 0);
    }

    /**
     * @param array<string, scalar> $data
     * @param array<string, string> $headers
     */
    public function postForm(string $url, array $data, array $headers = []): HttpResponse
    {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        return $this->request(
            'POST',
            $url,
            $headers,
            http_build_query($data, '', '&', PHP_QUERY_RFC3986),
            self::DEFAULT_MAX_BYTES,
        );
    }

    /**
     * @param array<string, string> $headers
     */
    private function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $maxBytes,
        int $redirects = 0,
    ): HttpResponse {
        if ($redirects > self::MAX_REDIRECTS) {
            throw new RuntimeException('Too many HTTP redirects.');
        }

        $url = $this->urlGuard->normalize($url);
        $headers += ['User-Agent' => 'FeedNews/2.0 (+https://feednews.me)', 'Accept' => '*/*'];

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headerLines),
                'content' => $body ?? '',
                'timeout' => 12,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $stream = @fopen($url, 'rb', false, $context);
        if ($stream === false) {
            throw new RuntimeException('Unable to connect to the remote URL.');
        }

        $metadata = stream_get_meta_data($stream);
        $rawHeaders = $metadata['wrapper_data'] ?? [];
        $responseBody = '';
        if ($method !== 'HEAD' && $maxBytes > 0) {
            $contents = stream_get_contents($stream, $maxBytes + 1);
            if ($contents === false) {
                fclose($stream);
                throw new RuntimeException('Unable to read the remote response.');
            }
            if (strlen($contents) > $maxBytes) {
                fclose($stream);
                throw new RuntimeException('The remote response is too large.');
            }
            $responseBody = $contents;
        }
        fclose($stream);

        $parsedHeaders = $this->parseHeaders(is_array($rawHeaders) ? $rawHeaders : []);
        $statusCode = $this->parseStatusCode(is_array($rawHeaders) ? $rawHeaders : []);

        if (in_array($statusCode, [301, 302, 303, 307, 308], true)) {
            $location = $parsedHeaders['location'][0] ?? null;
            if ($location === null) {
                throw new RuntimeException('Redirect response did not contain a Location header.');
            }

            return $this->request(
                $statusCode === 303 ? 'GET' : $method,
                $this->resolveRedirectUrl($url, $location),
                $headers,
                $statusCode === 303 ? null : $body,
                $maxBytes,
                $redirects + 1,
            );
        }

        return new HttpResponse($statusCode, $parsedHeaders, $responseBody, $url);
    }

    /**
     * @param array<array-key, mixed> $rawHeaders
     * @return array<string, list<string>>
     */
    private function parseHeaders(array $rawHeaders): array
    {
        $headers = [];
        foreach ($rawHeaders as $rawHeader) {
            if (!is_string($rawHeader) || !str_contains($rawHeader, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $rawHeader, 2);
            $headers[strtolower(trim($name))][] = trim($value);
        }

        return $headers;
    }

    /**
     * @param array<array-key, mixed> $rawHeaders
     */
    private function parseStatusCode(array $rawHeaders): int
    {
        foreach ($rawHeaders as $rawHeader) {
            if (is_string($rawHeader) && preg_match('~^HTTP/\S+\s+(\d{3})~', $rawHeader, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    private function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        if (preg_match('~^https?://~i', $location) === 1) {
            return $location;
        }

        $base = parse_url($baseUrl);
        if (!is_array($base) || !isset($base['scheme'], $base['host'])) {
            throw new RuntimeException('Unable to resolve redirect URL.');
        }

        if (str_starts_with($location, '//')) {
            return (string) $base['scheme'] . ':' . $location;
        }

        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $origin = (string) $base['scheme'] . '://' . (string) $base['host'] . $port;
        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }

        $path = isset($base['path']) ? dirname($base['path']) : '/';
        $path = $path === '.' ? '/' : rtrim($path, '/') . '/';

        return $origin . $path . $location;
    }
}
