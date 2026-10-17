<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedItemsPageResponse
{
    /**
     * @param list<FeedItemResponse> $items
     * @param list<FeedItemResponse> $pinnedItems
     */
    public function __construct(
        public array $items,
        public array $pinnedItems,
        public int $pinnedCount,
        public ?int $nextPage,
    ) {
    }
}
