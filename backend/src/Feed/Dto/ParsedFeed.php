<?php

declare(strict_types=1);

namespace App\Feed\Dto;

final readonly class ParsedFeed
{
    /**
     * @param list<ParsedFeedItem> $items
     */
    public function __construct(
        public string $title,
        public array $items,
    ) {
    }
}
