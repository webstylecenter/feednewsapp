<?php

declare(strict_types=1);

namespace App\Weather\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Weather\Dto\Response\WeatherResponse;
use App\Weather\Provider\WeatherProvider;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/weather',
        output: WeatherResponse::class,
        provider: WeatherProvider::class,
    ),
])]
final class WeatherDto
{
}
