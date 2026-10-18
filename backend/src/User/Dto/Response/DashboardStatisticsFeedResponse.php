<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class DashboardStatisticsFeedResponse
{
    public function __construct(
        public string $name,
        public int $count,
    ) {
    }
}
