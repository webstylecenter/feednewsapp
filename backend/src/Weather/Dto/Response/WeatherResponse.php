<?php

declare(strict_types=1);

namespace App\Weather\Dto\Response;

final readonly class WeatherResponse
{
    public function __construct(public WeatherForecastResponse $forecast)
    {
    }
}
