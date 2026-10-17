<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedOverviewItemResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $color,
        public string $icon,
        public bool $autoPin,
        public int $itemCount,
    ) {
    }
}
