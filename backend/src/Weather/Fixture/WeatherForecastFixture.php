<?php

declare(strict_types=1);

namespace App\Weather\Fixture;

use App\Weather\Entity\WeatherForecast;
use App\Weather\Entity\WeatherForecastDay;
use App\Weather\Enum\WeatherType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use Random\RandomException;

final class WeatherForecastFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @throws RandomException
     */
    #[Override]
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
                currentTemp: 14 + random_int(-3, 6),
                currentWeather: $weather,
            );

            $weatherForecast->setForecastDays(
                new WeatherForecastDay(0, 18 + random_int(0, 7), 10 + random_int(-2, 3), $weather),
                new WeatherForecastDay(1, 18 + random_int(0, 7), 10 + random_int(-2, 3), WeatherType::PARTLY_CLOUD),
                new WeatherForecastDay(2, 18 + random_int(0, 7), 10 + random_int(-2, 3), WeatherType::RAIN),
                new WeatherForecastDay(3, 18 + random_int(0, 7), 10 + random_int(-2, 3), WeatherType::CLOUD),
                new WeatherForecastDay(4, 18 + random_int(0, 7), 10 + random_int(-2, 3), WeatherType::SUN),
                new WeatherForecastDay(5, 18 + random_int(0, 7), 10 + random_int(-2, 3), WeatherType::SUN),
            );

            $manager->persist($weatherForecast);
        }

        $manager->flush();
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['weather'];
    }
}
