<?php

declare(strict_types=1);

namespace App\Weather\Mapper;

use App\Weather\Dto\Response\WeatherDayResponse;
use App\Weather\Dto\Response\WeatherForecastResponse;
use App\Weather\Entity\WeatherForecast;
use App\Weather\Entity\WeatherForecastDay;

final class WeatherMapper
{
    public function toResponse(WeatherForecast $forecast): WeatherForecastResponse
    {
        return new WeatherForecastResponse(
            location: $forecast->getLocation(),
            currentTemp: $forecast->getCurrentTemp(),
            currentWeather: $forecast->getCurrentWeather()->value,
            updatedAt: $forecast->getUpdatedAt()->format(DATE_ATOM),
            days: array_map(
                static fn (WeatherForecastDay $day): WeatherDayResponse => new WeatherDayResponse(
                    dayOffset: $day->getDayOffset(),
                    tempMax: $day->getTempMax(),
                    tempMin: $day->getTempMin(),
                    weather: $day->getWeather()->value,
                ),
                $forecast->getForecastDays()->toArray(),
            ),
        );
    }
}
