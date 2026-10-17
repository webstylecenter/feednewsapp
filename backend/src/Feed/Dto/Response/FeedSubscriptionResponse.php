<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedSubscriptionResponse
{
    public function __construct(
        public int $id,
        public FeedSummaryResponse $feed,
        public string $color,
        public string $icon,
        public bool $autoPin,
    ) {
    }
}
