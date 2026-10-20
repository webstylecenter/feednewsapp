<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Dto\Response\ReaderResponse;
use App\Shared\Http\HttpResponse;
use App\Shared\Http\NativeHttpClient;
use App\Shared\Http\PublicUrlGuard;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class ReaderService
{
    private const int MAX_HTML_BYTES = 4_000_000;
    private const int CACHE_TTL_SECONDS = 600;

    /** @var list<int> */
    private const array FALLBACK_STATUS_CODES = [0, 403, 406, 408, 425, 429, 500, 502, 503, 504];

    public function __construct(
        private NativeHttpClient $httpClient,
        private PublicUrlGuard $urlGuard,
        private BrowserRendererClient $browserRendererClient,
        private CacheInterface $cache,
    ) {
    }

    public function fetch(string $url): ReaderResponse
    {
        $safeUrl = $this->urlGuard->normalize($url);
        $cacheKey = 'reader_' . hash('sha256', $safeUrl);

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($safeUrl): ReaderResponse {
                $item->expiresAfter(self::CACHE_TTL_SECONDS);

                return $this->fetchUncached($safeUrl);
            },
        );
    }

    private function fetchUncached(string $url): ReaderResponse
    {
        if ($this->browserRendererClient->prefersBrowser($url)) {
            try {
                return $this->fetchViaBrowserRenderer($url);
            } catch (RuntimeException) {
                // Fall through to the direct reader when the local renderer is unavailable.
            }
        }

        try {
            $response = $this->httpClient->get(
                $url,
                $this->browserHeaders($url),
                self::MAX_HTML_BYTES,
            );
        } catch (RuntimeException $exception) {
            return $this->fetchViaBrowserRenderer($url, $exception);
        }

        if (in_array($response->statusCode, self::FALLBACK_STATUS_CODES, true)) {
            return $this->fetchViaBrowserRenderer(
                $url,
                new RuntimeException(sprintf('The website returned HTTP %d.', $response->statusCode)),
            );
        }

        $this->assertDirectResponseIsReadable($response);

        if ($this->looksLikeBrowserChallenge($response->body)) {
            return $this->fetchViaBrowserRenderer(
                $url,
                new RuntimeException('The website returned a browser verification page.'),
            );
        }

        return $this->createResponse(
            $response->body,
            $response->url,
            strtolower($response->firstHeader('content-type') ?? ''),
        );
    }

    private function fetchViaBrowserRenderer(string $url, ?RuntimeException $directException = null): ReaderResponse
    {
        try {
            $renderedPage = $this->browserRendererClient->render($url);
        } catch (RuntimeException $rendererException) {
            throw $directException ?? $rendererException;
        }

        return $this->createResponse(
            $renderedPage['html'],
            $renderedPage['url'],
            'text/html; charset=utf-8',
        );
    }

    private function assertDirectResponseIsReadable(HttpResponse $response): void
    {
        if ($response->statusCode < 200 || $response->statusCode >= 400) {
            throw new RuntimeException(sprintf('The website returned HTTP %d.', $response->statusCode));
        }

        $contentType = strtolower($response->firstHeader('content-type') ?? '');
        if (
            $contentType !== ''
            && !str_contains($contentType, 'text/html')
            && !str_contains($contentType, 'application/xhtml+xml')
        ) {
            throw new RuntimeException('The URL did not return an HTML document.');
        }
    }

    private function looksLikeBrowserChallenge(string $html): bool
    {
        $sample = strtolower(substr($html, 0, 200_000));

        foreach ([
            'cf-chl-',
            'challenge-platform',
            'checking your browser',
            'verify you are human',
            'enable javascript and cookies',
            'access denied',
        ] as $marker) {
            if (str_contains($sample, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function createResponse(string $html, string $url, string $contentType): ReaderResponse
    {
        $html = $this->toUtf8($html, $contentType);
        [$siteName, $mainImage] = $this->extractMetadata($html, $url);

        return new ReaderResponse(
            url: $url,
            html: $html,
            siteName: $siteName,
            mainImage: $mainImage,
        );
    }

    /** @return array<string, string> */
    private function browserHeaders(string $url): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                . 'Chrome/155.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'DNT' => '1',
            'Pragma' => 'no-cache',
            'Referer' => $this->origin($url) . '/',
            'Sec-CH-UA' => '"Chromium";v="155", "Google Chrome";v="155", "Not_A Brand";v="99"',
            'Sec-CH-UA-Mobile' => '?0',
            'Sec-CH-UA-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
        ];
    }

    private function origin(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return (string) $parts['scheme'] . '://' . (string) $parts['host'] . $port;
    }

    private function toUtf8(string $html, string $contentType): string
    {
        $encoding = null;

        if (preg_match('/charset\s*=\s*["\']?([a-z0-9._-]+)/i', $contentType, $matches) === 1) {
            $encoding = $matches[1];
        }

        if (
            $encoding === null
            && preg_match('/<meta[^>]+charset=["\']?\s*([^\s"\'>]+)/i', $html, $matches) === 1
        ) {
            $encoding = $matches[1];
        }

        $encoding ??= mb_detect_encoding(
            $html,
            ['UTF-8', 'Windows-1252', 'ISO-8859-1'],
            true,
        ) ?: 'UTF-8';

        if (strcasecmp($encoding, 'UTF-8') === 0) {
            return $html;
        }

        $converted = mb_convert_encoding($html, 'UTF-8', $encoding);

        if ($converted === false) {
            throw new RuntimeException('Could not convert reader HTML to UTF-8.');
        }

        return $converted;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function extractMetadata(string $html, string $url): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return [$this->hostName($url), null];
        }

        $xpath = new DOMXPath($document);
        $siteName = $this->metaContent($xpath, 'property', 'og:site_name')
            ?? $this->metaContent($xpath, 'name', 'application-name')
            ?? $this->hostName($url);
        $mainImage = $this->metaContent($xpath, 'property', 'og:image')
            ?? $this->metaContent($xpath, 'property', 'og:image:url')
            ?? $this->metaContent($xpath, 'name', 'twitter:image');

        return [
            $siteName,
            $mainImage === null ? null : $this->absoluteUrl($mainImage, $url),
        ];
    }

    private function metaContent(DOMXPath $xpath, string $attribute, string $value): ?string
    {
        $nodes = $xpath->query(sprintf(
            '//meta[translate(@%s, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="%s"]',
            $attribute,
            strtolower($value),
        ));
        if ($nodes === false) {
            return null;
        }

        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            return null;
        }

        $content = trim($node->getAttribute('content'));

        return $content === '' ? null : $content;
    }

    private function hostName(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? preg_replace('/^www\./i', '', $host) : null;
    }

    private function absoluteUrl(string $candidate, string $baseUrl): string
    {
        if (preg_match('~^https?://~i', $candidate) === 1) {
            return $candidate;
        }

        $base = parse_url($baseUrl);
        if (!is_array($base) || !isset($base['scheme'], $base['host'])) {
            return $candidate;
        }

        if (str_starts_with($candidate, '//')) {
            return (string) $base['scheme'] . ':' . $candidate;
        }

        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $origin = (string) $base['scheme'] . '://' . (string) $base['host'] . $port;
        if (str_starts_with($candidate, '/')) {
            return $origin . $candidate;
        }

        $path = isset($base['path']) ? dirname((string) $base['path']) : '/';
        $path = $path === '.' ? '/' : rtrim($path, '/') . '/';

        return $origin . $path . $candidate;
    }
}
