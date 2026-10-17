<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedOverviewResponse
{
    /** @param list<FeedOverviewItemResponse> $items */
    public function __construct(public array $items)
    {
    }
}
