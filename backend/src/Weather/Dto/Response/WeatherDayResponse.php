<?php

declare(strict_types=1);

namespace App\Weather\Dto\Response;

final readonly class WeatherDayResponse
{
    public function __construct(
        public int $dayOffset,
        public float $tempMax,
        public float $tempMin,
        public string $weather,
    ) {
    }
}
