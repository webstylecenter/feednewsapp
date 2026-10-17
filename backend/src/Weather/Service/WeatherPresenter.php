<?php

declare(strict_types=1);

namespace App\Weather\Service;

use App\Weather\Entity\WeatherForecast;
use App\Weather\Entity\WeatherForecastDay;

final class WeatherPresenter
{
    /** @return array<string, mixed> */
    public function present(WeatherForecast $forecast): array
    {
        return [
            'location' => $forecast->getLocation(),
            'currentTemp' => $forecast->getCurrentTemp(),
            'currentWeather' => $forecast->getCurrentWeather()->value,
            'updatedAt' => $forecast->getUpdatedAt()->format(DATE_ATOM),
            'days' => array_map(
                static fn (WeatherForecastDay $day): array => [
                    'dayOffset' => $day->getDayOffset(),
                    'tempMax' => $day->getTempMax(),
                    'tempMin' => $day->getTempMin(),
                    'weather' => $day->getWeather()->value,
                ],
                $forecast->getForecastDays()->toArray(),
            ),
        ];
    }
}
