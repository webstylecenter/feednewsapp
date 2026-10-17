<?php

declare(strict_types=1);

namespace App\Weather\Dto\Response;

final readonly class WeatherForecastResponse
{
    /** @param list<WeatherDayResponse> $days */
    public function __construct(
        public string $location,
        public float $currentTemp,
        public string $currentWeather,
        public string $updatedAt,
        public array $days,
    ) {
    }
}
