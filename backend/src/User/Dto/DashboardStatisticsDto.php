<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\User\Dto\Response\DashboardStatisticsResponse;
use App\User\Provider\DashboardStatisticsProvider;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/dashboard/statistics',
        output: DashboardStatisticsResponse::class,
        provider: DashboardStatisticsProvider::class,
    ),
])]
final class DashboardStatisticsDto
{
}
