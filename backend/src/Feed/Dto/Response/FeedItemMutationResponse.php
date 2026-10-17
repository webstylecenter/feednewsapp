<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedItemMutationResponse
{
    public function __construct(
        public FeedItemResponse $item,
        public string $status = 'success',
    ) {
    }
}
