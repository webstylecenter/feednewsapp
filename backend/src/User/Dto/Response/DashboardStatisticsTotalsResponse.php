<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class DashboardStatisticsTotalsResponse
{
    public function __construct(
        public int $items,
        public int $unread,
        public int $opened,
        public int $pinned,
    ) {
    }
}
