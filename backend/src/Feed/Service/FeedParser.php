<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Dto\ParsedFeed;
use App\Feed\Dto\ParsedFeedItem;
use App\Feed\Repository\FeedItemRepository;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use RuntimeException;
use Throwable;

final class FeedParser
{
    public function __construct(
        private readonly FeedItemRepository $feedItemRepository,
        private readonly YouTubeVideoFilter $youTubeVideoFilter,
    ) {
    }

    public function parse(string $xml): ParsedFeed
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if ($document->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT) === false) {
                throw new RuntimeException('The URL did not return valid RSS or Atom XML.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $root = $document->documentElement;
        if (!$root instanceof DOMElement) {
            throw new RuntimeException('The feed does not contain a document element.');
        }

        return strtolower((string) $root->localName) === 'feed'
            ? $this->parseAtom($root)
            : $this->parseRss($root);
    }

    private function parseRss(DOMElement $root): ParsedFeed
    {
        $channel = $this->firstDescendant($root, 'channel') ?? $root;
        $title = $this->childText($channel, 'title') ?? 'Untitled feed';
        $items = [];
        $skippedShort = false;

        foreach ($root->getElementsByTagName('*') as $node) {
            if (strtolower((string) $node->localName) !== 'item') {
                continue;
            }

            $itemTitle = $this->childText($node, 'title') ?? 'Untitled item';
            $url = $this->childText($node, 'link') ?? '';
            if ($url === '') {
                continue;
            }

            $guid = $this->childText($node, 'guid') ?? $url;
            if ($this->shouldSkipYouTubeShort($guid, $url)) {
                $skippedShort = true;
                continue;
            }

            $description = $this->childText($node, 'description')
                ?? $this->childText($node, 'encoded')
                ?? $this->childText($node, 'content');
            $publishedAt = $this->parseDate(
                $this->childText($node, 'pubDate')
                ?? $this->childText($node, 'date'),
            );

            $items[] = new ParsedFeedItem(
                $guid,
                $this->cleanText($itemTitle, 255),
                $description !== null ? $this->cleanText($description, 500) : null,
                trim($url),
                $publishedAt,
            );
        }

        if ($items === [] && !$skippedShort) {
            throw new RuntimeException('The RSS feed does not contain any readable items.');
        }

        return new ParsedFeed($this->cleanText($title, 255), $items);
    }

    private function parseAtom(DOMElement $root): ParsedFeed
    {
        $title = $this->childText($root, 'title') ?? 'Untitled feed';
        $items = [];
        $skippedShort = false;

        foreach ($root->childNodes as $node) {
            if (!$node instanceof DOMElement || strtolower((string) $node->localName) !== 'entry') {
                continue;
            }

            $url = $this->atomLink($node);
            if ($url === null) {
                continue;
            }

            $itemTitle = $this->childText($node, 'title') ?? 'Untitled item';
            $guid = $this->childText($node, 'id') ?? $url;
            if ($this->shouldSkipYouTubeShort($guid, $url)) {
                $skippedShort = true;
                continue;
            }

            $description = $this->childText($node, 'summary') ?? $this->childText($node, 'content');
            $publishedAt = $this->parseDate(
                $this->childText($node, 'published')
                ?? $this->childText($node, 'updated'),
            );

            $items[] = new ParsedFeedItem(
                $guid,
                $this->cleanText($itemTitle, 255),
                $description !== null ? $this->cleanText($description, 500) : null,
                $url,
                $publishedAt,
            );
        }

        if ($items === [] && !$skippedShort) {
            throw new RuntimeException('The Atom feed does not contain any readable entries.');
        }

        return new ParsedFeed($this->cleanText($title, 255), $items);
    }

    private function shouldSkipYouTubeShort(string $guid, string $url): bool
    {
        if (!$this->isYouTubeVideoUrl($url)) {
            return false;
        }

        if ($this->feedItemRepository->findOneBy(['guid' => $guid]) !== null) {
            return false;
        }

        return $this->youTubeVideoFilter->isShort($url);
    }

    private function isYouTubeVideoUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host)
            && ($host === 'youtu.be' || $host === 'youtube.com' || str_ends_with($host, '.youtube.com'));
    }

    private function atomLink(DOMElement $entry): ?string
    {
        $fallback = null;
        foreach ($entry->childNodes as $node) {
            if (!$node instanceof DOMElement || strtolower((string) $node->localName) !== 'link') {
                continue;
            }

            $href = trim($node->getAttribute('href'));
            if ($href === '') {
                continue;
            }

            $rel = strtolower($node->getAttribute('rel'));
            if ($rel === '' || $rel === 'alternate') {
                return $href;
            }
            $fallback ??= $href;
        }

        return $fallback;
    }

    private function firstDescendant(DOMElement $element, string $localName): ?DOMElement
    {
        foreach ($element->getElementsByTagName('*') as $node) {
            if (strtolower((string) $node->localName) === strtolower($localName)) {
                return $node;
            }
        }

        return null;
    }

    private function childText(DOMElement $element, string $localName): ?string
    {
        foreach ($element->childNodes as $node) {
            if (!$node instanceof DOMElement || strtolower((string) $node->localName) !== strtolower($localName)) {
                continue;
            }

            $value = trim($node->textContent);
            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function cleanText(string $value, int $maxLength): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim(mb_substr($value, 0, $maxLength));
    }

    private function parseDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }
}
