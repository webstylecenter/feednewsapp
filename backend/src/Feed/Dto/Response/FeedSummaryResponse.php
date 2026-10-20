<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedSummaryResponse
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $url,
        public string $color,
        public ?string $category,
        public ?string $lastItemAt = null,
        public ?int $httpErrorStatus = null,
        public ?string $httpErrorMessage = null,
    ) {
    }
}
