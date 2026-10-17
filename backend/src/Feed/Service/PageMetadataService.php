<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Dto\PageMetadata;
use App\Shared\Http\NativeHttpClient;
use DOMDocument;
use DOMElement;
use RuntimeException;

final readonly class PageMetadataService
{
    public function __construct(private NativeHttpClient $httpClient)
    {
    }

    public function fetch(string $url): PageMetadata
    {
        $response = $this->httpClient->get($url, [
            'Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.1',
        ]);

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new RuntimeException(sprintf('Page returned HTTP %d.', $response->statusCode));
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $document->loadHTML($response->body, LIBXML_NONET | LIBXML_COMPACT);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $title = $this->findMeta($document, 'property', 'og:title')
            ?? $this->findMeta($document, 'name', 'twitter:title')
            ?? $document->getElementsByTagName('title')->item(0)?->textContent
            ?? $response->url;
        $description = $this->findMeta($document, 'property', 'og:description')
            ?? $this->findMeta($document, 'name', 'description')
            ?? $this->findMeta($document, 'name', 'twitter:description')
            ?? '';

        return new PageMetadata(
            $response->url,
            $this->clean($title, 255),
            $this->clean($description, 500),
        );
    }

    private function findMeta(DOMDocument $document, string $attribute, string $expectedValue): ?string
    {
        foreach ($document->getElementsByTagName('meta') as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            if (strcasecmp($node->getAttribute($attribute), $expectedValue) !== 0) {
                continue;
            }

            $content = trim($node->getAttribute('content'));
            if ($content !== '') {
                return $content;
            }
        }

        return null;
    }

    private function clean(string $value, int $maxLength): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim(mb_substr($value, 0, $maxLength));
    }
}
