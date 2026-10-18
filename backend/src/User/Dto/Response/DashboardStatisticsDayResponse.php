<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class DashboardStatisticsDayResponse
{
    public function __construct(
        public string $date,
        public int $count,
    ) {
    }
}
