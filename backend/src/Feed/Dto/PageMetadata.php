<?php

declare(strict_types=1);

namespace App\Feed\Dto;

final readonly class PageMetadata
{
    public function __construct(
        public string $url,
        public string $title,
        public string $description,
    ) {
    }
}
