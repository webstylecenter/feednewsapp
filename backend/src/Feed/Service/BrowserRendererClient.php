<?php

declare(strict_types=1);

namespace App\Feed\Service;

use JsonException;
use RuntimeException;

final readonly class BrowserRendererClient
{
    private const int MAX_RESPONSE_BYTES = 5_000_000;
    private const int TIMEOUT_SECONDS = 20;

    public function __construct(
        private string $endpoint,
        private string $preferredHosts,
    ) {
    }

    public function prefersBrowser(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);
        foreach ($this->hosts() as $preferredHost) {
            if ($host === $preferredHost || str_ends_with($host, '.' . $preferredHost)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{url: string, html: string} */
    public function render(string $url): array
    {
        try {
            $payload = json_encode(['url' => $url], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not encode browser renderer request.', 0, $exception);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\nConnection: close",
                'content' => $payload,
                'timeout' => self::TIMEOUT_SECONDS,
                'ignore_errors' => true,
            ],
        ]);

        $stream = @fopen($this->endpoint, 'rb', false, $context);
        if ($stream === false) {
            throw new RuntimeException('The local browser renderer is unavailable.');
        }

        $metadata = stream_get_meta_data($stream);
        $rawHeaders = $metadata['wrapper_data'] ?? [];
        $responseBody = stream_get_contents($stream, self::MAX_RESPONSE_BYTES + 1);
        fclose($stream);

        if ($responseBody === false) {
            throw new RuntimeException('Could not read the local browser renderer response.');
        }
        if (strlen($responseBody) > self::MAX_RESPONSE_BYTES) {
            throw new RuntimeException('The local browser renderer response is too large.');
        }

        $statusCode = $this->statusCode(is_array($rawHeaders) ? $rawHeaders : []);
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException(sprintf('The local browser renderer returned HTTP %d.', $statusCode));
        }

        try {
            $response = json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The local browser renderer returned invalid JSON.', 0, $exception);
        }

        if (!is_array($response)) {
            throw new RuntimeException('The local browser renderer returned an invalid response.');
        }

        $finalUrl = $response['url'] ?? null;
        $html = $response['html'] ?? null;
        if (!is_string($finalUrl) || $finalUrl === '' || !is_string($html) || trim($html) === '') {
            throw new RuntimeException('The local browser renderer returned no readable page.');
        }

        return ['url' => $finalUrl, 'html' => $html];
    }

    /** @return list<string> */
    private function hosts(): array
    {
        $hosts = array_map(
            static fn (string $host): string => strtolower(trim($host)),
            explode(',', $this->preferredHosts),
        );

        return array_values(array_filter($hosts, static fn (string $host): bool => $host !== ''));
    }

    /** @param array<array-key, mixed> $rawHeaders */
    private function statusCode(array $rawHeaders): int
    {
        foreach ($rawHeaders as $rawHeader) {
            if (is_string($rawHeader) && preg_match('~^HTTP/\S+\s+(\d{3})~', $rawHeader, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
