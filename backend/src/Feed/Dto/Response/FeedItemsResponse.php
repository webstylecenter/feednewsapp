<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedItemsResponse
{
    /** @param list<FeedItemResponse> $items */
    public function __construct(public array $items)
    {
    }
}
