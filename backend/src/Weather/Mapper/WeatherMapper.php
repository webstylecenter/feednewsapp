<?php

declare(strict_types=1);

namespace App\Weather\Mapper;

use App\Weather\Dto\Response\WeatherDayResponse;
use App\Weather\Dto\Response\WeatherForecastResponse;
use App\Weather\Entity\WeatherForecast;
use App\Weather\Entity\WeatherForecastDay;
use DateTimeInterface;
use LogicException;

final class WeatherMapper
{
    public function toResponse(WeatherForecast $forecast): WeatherForecastResponse
    {
        $days = array_map(
            static fn (WeatherForecastDay $day): WeatherDayResponse => new WeatherDayResponse(
                dayOffset: $day->getDayOffset(),
                tempMax: $day->getTempMax(),
                tempMin: $day->getTempMin(),
                weather: $day->getWeather()->value,
            ),
            $forecast->getForecastDays()->toArray(),
        );

        return new WeatherForecastResponse(
            location: $forecast->getLocation(),
            currentTemp: $forecast->getCurrentTemp(),
            currentWeather: $forecast->getCurrentWeather()->value,
            updatedAt: self::formatTimestamp($forecast->getUpdatedAt()),
            days: array_values($days),
        );
    }

    private static function formatTimestamp(?DateTimeInterface $dateTime): string
    {
        if ($dateTime === null) {
            throw new LogicException('Persisted weather forecast is missing an update timestamp.');
        }

        return $dateTime->format(DATE_ATOM);
    }
}
