<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class PinFeedItemResponse
{
    public function __construct(
        public bool $currentState,
        public FeedItemResponse $item,
        public string $status = 'success',
    ) {
    }
}
