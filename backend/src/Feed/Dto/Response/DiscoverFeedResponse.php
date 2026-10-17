<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class DiscoverFeedResponse
{
    public function __construct(
        public string $feedUrl,
        public string $status = 'success',
    ) {
    }
}
