<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class DashboardFeedResponse
{
    public function __construct(
        public string $source,
        public string $name,
        public string $color,
        public string $icon,
        public int $itemCount,
        public ?string $url,
    ) {
    }
}
