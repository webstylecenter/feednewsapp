<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class PageMetadataDataResponse
{
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
    ) {
    }
}
