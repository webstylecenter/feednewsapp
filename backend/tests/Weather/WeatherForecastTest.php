<?php

declare(strict_types=1);

namespace App\Tests\Weather;

use App\Weather\Entity\WeatherForecast;
use App\Weather\Entity\WeatherForecastDay;
use App\Weather\Enum\WeatherType;
use PHPUnit\Framework\TestCase;

final class WeatherForecastTest extends TestCase
{
    public function testForecastDaysAreManagedBidirectionally(): void
    {
        $forecast = new WeatherForecast('Amsterdam', 12.5, WeatherType::SUN);
        $day = new WeatherForecastDay($forecast, 1, 18.0, 10.0, WeatherType::CLOUD);

        $forecast->addForecastDay($day);

        self::assertSame($forecast, $day->getForecast());
        self::assertTrue($forecast->getForecastDays()->contains($day));

        $forecast->removeForecastDay($day);
        self::assertFalse($forecast->getForecastDays()->contains($day));
    }
}
