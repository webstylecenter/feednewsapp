<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Shared\Http\NativeHttpClient;
use App\Shared\Http\PublicUrlGuard;
use DOMDocument;
use DOMElement;
use RuntimeException;

final readonly class FeedDiscoveryService
{
    public function __construct(
        private NativeHttpClient $httpClient,
        private PublicUrlGuard $urlGuard,
    ) {
    }

    public function resolve(string $feedUrl, string $websiteUrl): string
    {
        if (trim($feedUrl) !== '') {
            return $this->urlGuard->normalize($feedUrl);
        }

        if (trim($websiteUrl) === '') {
            throw new RuntimeException('Enter a website URL or RSS/Atom feed URL.');
        }

        $websiteUrl = $this->urlGuard->normalize($websiteUrl);
        $youtubeFeed = $this->youtubeFeedUrl($websiteUrl);
        if ($youtubeFeed !== null) {
            return $youtubeFeed;
        }

        $response = $this->httpClient->get($websiteUrl, [
            'Accept' => 'application/rss+xml,application/atom+xml,application/xml,text/xml,text/html,application/xhtml+xml;q=0.9,*/*;q=0.1',
        ]);
        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new RuntimeException(sprintf('Website returned HTTP %d.', $response->statusCode));
        }

        $trimmedBody = ltrim($response->body);
        if (preg_match('/^<\?xml\b|^<(?:rss|feed|rdf:RDF)\b/i', $trimmedBody) === 1) {
            return $response->url;
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $document->loadHTML($response->body, LIBXML_NONET | LIBXML_COMPACT);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        foreach ($document->getElementsByTagName('link') as $link) {
            if (!$link instanceof DOMElement) {
                continue;
            }

            $rel = strtolower($link->getAttribute('rel'));
            $type = strtolower($link->getAttribute('type'));
            if (
                !str_contains($rel, 'alternate') || !in_array($type, [
                'application/rss+xml',
                'application/atom+xml',
                'text/xml',
                'application/xml',
                ], true)
            ) {
                continue;
            }

            $href = trim($link->getAttribute('href'));
            if ($href !== '') {
                return $this->urlGuard->normalize($this->resolveUrl($response->url, $href));
            }
        }

        throw new RuntimeException('No RSS or Atom feed was found on that website.');
    }

    private function youtubeFeedUrl(string $url): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['host'], $parts['path'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        if (!in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            return null;
        }

        if (preg_match('~^/channel/([^/]+)~', (string) $parts['path'], $matches) === 1) {
            return 'https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode($matches[1]);
        }

        if (preg_match('~^/user/([^/]+)~', (string) $parts['path'], $matches) === 1) {
            return 'https://www.youtube.com/feeds/videos.xml?user=' . rawurlencode($matches[1]);
        }

        return null;
    }

    private function resolveUrl(string $baseUrl, string $href): string
    {
        if (preg_match('~^https?://~i', $href) === 1) {
            return $href;
        }

        $base = parse_url($baseUrl);
        if (!is_array($base) || !isset($base['scheme'], $base['host'])) {
            throw new RuntimeException('Could not resolve the discovered feed URL.');
        }

        if (str_starts_with($href, '//')) {
            return (string) $base['scheme'] . ':' . $href;
        }

        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $origin = (string) $base['scheme'] . '://' . (string) $base['host'] . $port;
        if (str_starts_with($href, '/')) {
            return $origin . $href;
        }

        $directory = isset($base['path']) ? dirname($base['path']) : '/';
        $directory = $directory === '.' ? '/' : rtrim($directory, '/') . '/';
        return $origin . $directory . $href;
    }
}
