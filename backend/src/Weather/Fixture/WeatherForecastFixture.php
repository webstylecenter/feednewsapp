<?php

declare(strict_types=1);

namespace App\Weather\Fixture;

use App\Weather\Entity\WeatherForecast;
use App\Weather\Enum\WeatherType;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class WeatherForecastFixture extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $cities = [
            ['Amsterdam', WeatherType::PARTLY_CLOUD],
            ['Berlin', WeatherType::SUN],
            ['Madrid', WeatherType::SUN],
        ];

        foreach ($cities as [$city, $weather]) {
            $weatherForecast = new WeatherForecast(
                location: $city,
                currentTemp: 14 + rand(-3, 6),
                currentWeather: $weather,
                tempTodayMax: 18 + rand(0, 7),
                tempTodayMin: 10 + rand(-2, 3),
                tempIn1DaysMax: 18 + rand(0, 7),
                tempIn1DaysMin: 10 + rand(-2, 3),
                tempIn2DaysMax: 18 + rand(0, 7),
                tempIn2DaysMin: 10 + rand(-2, 3),
                tempIn3DaysMax: 18 + rand(0, 7),
                tempIn3DaysMin: 10 + rand(-2, 3),
                tempIn4DaysMax: 18 + rand(0, 7),
                tempIn4DaysMin: 10 + rand(-2, 3),
                tempIn5DaysMax: 18 + rand(0, 7),
                tempIn5DaysMin: 10 + rand(-2, 3),
                weatherIn1Days: WeatherType::PARTLY_CLOUD,
                weatherIn2Days: WeatherType::RAIN,
                weatherIn3Days: WeatherType::CLOUD,
                weatherIn4Days: WeatherType::SUN,
                weatherIn5Days: WeatherType::SUN,
            );

            $manager->persist($weatherForecast);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['weather'];
    }
}
