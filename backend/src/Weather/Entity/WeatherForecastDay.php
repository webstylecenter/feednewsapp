<?php

declare(strict_types=1);

namespace App\Weather\Entity;

use App\Weather\Enum\WeatherType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'weather_forecast_day',
    indexes: [
        new ORM\Index(name: 'idx_weather_forecast_day_forecast', columns: ['forecast_id'])
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_weather_forecast_day', columns: ['forecast_id', 'day_offset'])
    ]
)]
class WeatherForecastDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WeatherForecast::class, inversedBy: 'forecastDays')]
    #[ORM\JoinColumn(name: 'forecast_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WeatherForecast $forecast;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $dayOffset;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempMax;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempMin;

    #[ORM\Column(type: Types::STRING, enumType: WeatherType::class)]
    private WeatherType $weather;

    public function __construct(
        int $dayOffset,
        float $tempMax,
        float $tempMin,
        WeatherType $weather
    ) {
        $this->dayOffset = $dayOffset;
        $this->tempMax = $tempMax;
        $this->tempMin = $tempMin;
        $this->weather = $weather;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForecast(): ?WeatherForecast
    {
        return $this->forecast;
    }

    public function setForecast(WeatherForecast $forecast): WeatherForecastDay
    {
        $forecast->getForecastDays()->add($this);
        $this->forecast = $forecast;

        return $this;
    }

    public function getDayOffset(): int
    {
        return $this->dayOffset;
    }

    public function setDayOffset(int $dayOffset): WeatherForecastDay
    {
        $this->dayOffset = $dayOffset;
        return $this;
    }

    public function getTempMax(): float
    {
        return $this->tempMax;
    }

    public function setTempMax(float $tempMax): WeatherForecastDay
    {
        $this->tempMax = $tempMax;
        return $this;
    }

    public function getTempMin(): float
    {
        return $this->tempMin;
    }

    public function setTempMin(float $tempMin): WeatherForecastDay
    {
        $this->tempMin = $tempMin;
        return $this;
    }

    public function getWeather(): WeatherType
    {
        return $this->weather;
    }

    public function setWeather(WeatherType $weather): WeatherForecastDay
    {
        $this->weather = $weather;
        return $this;
    }
}
