<?php

declare(strict_types=1);

namespace App\Feed\Dto;

use DateTimeImmutable;

final readonly class ParsedFeedItem
{
    public function __construct(
        public string $guid,
        public string $title,
        public ?string $description,
        public string $url,
        public ?DateTimeImmutable $publishedAt,
    ) {
    }
}
