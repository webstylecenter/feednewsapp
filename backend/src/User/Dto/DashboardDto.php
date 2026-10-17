<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\User\Dto\Response\DashboardResponse;
use App\User\Provider\DashboardProvider;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/dashboard',
        output: DashboardResponse::class,
        provider: DashboardProvider::class,
    ),
])]
final class DashboardDto
{
}
