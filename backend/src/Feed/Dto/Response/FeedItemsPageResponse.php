<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedItemsPageResponse
{
    /** @param list<FeedItemResponse> $items */
    public function __construct(
        public array $items,
        public ?int $nextPage,
    ) {
    }
}
