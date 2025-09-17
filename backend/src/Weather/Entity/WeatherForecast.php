<?php

declare(strict_types=1);

namespace App\Weather\Entity;

use App\Weather\Enum\WeatherType;
use App\Weather\Repository\WeatherForecastRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: WeatherForecastRepository::class)]
#[ORM\Table(name: 'weather_forecast')]
class WeatherForecast
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: Types::STRING)]
    private string $location;

    #[ORM\Column(type: Types::FLOAT)]
    private float $CurrentTemp;

    #[ORM\Column(type: Types::STRING, enumType: WeatherType::class)]
    private WeatherType $currentWeather;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempTodayMax;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempTodayMin;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn1DaysMax;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn1DaysMin;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn2DaysMax;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn2DaysMin;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn3DaysMax;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn3DaysMin;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn4DaysMax;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn4DaysMin;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn5DaysMax;

    #[ORM\Column(type: Types::FLOAT)]
    private float $tempIn5DaysMin;

    #[ORM\Column(type: Types::STRING, enumType: WeatherType::class)]
    private WeatherType $weatherIn1Days;

    #[ORM\Column(type: Types::STRING, enumType: WeatherType::class)]
    private WeatherType $weatherIn2Days;

    #[ORM\Column(type: Types::STRING, enumType: WeatherType::class)]
    private WeatherType $weatherIn3Days;

    #[ORM\Column(type: Types::STRING, enumType: WeatherType::class)]
    private WeatherType $weatherIn4Days;

    #[ORM\Column(type: Types::STRING, enumType: WeatherType::class)]
    private WeatherType $weatherIn5Days;

    public function __construct(
        string $location,
        float $CurrentTemp,
        WeatherType $currentWeather,
        float $tempTodayMax,
        float $tempTodayMin,
        float $tempIn1DaysMax,
        float $tempIn1DaysMin,
        float $tempIn2DaysMax,
        float $tempIn2DaysMin,
        float $tempIn3DaysMax,
        float $tempIn3DaysMin,
        float $tempIn4DaysMax,
        float $tempIn4DaysMin,
        float $tempIn5DaysMax,
        float $tempIn5DaysMin,
        WeatherType $weatherIn1Days,
        WeatherType $weatherIn2Days,
        WeatherType $weatherIn3Days,
        WeatherType $weatherIn4Days,
        WeatherType $weatherIn5Days
    ) {
        $this->location = $location;
        $this->CurrentTemp = $CurrentTemp;
        $this->currentWeather = $currentWeather;
        $this->tempTodayMax = $tempTodayMax;
        $this->tempTodayMin = $tempTodayMin;
        $this->tempIn1DaysMax = $tempIn1DaysMax;
        $this->tempIn1DaysMin = $tempIn1DaysMin;
        $this->tempIn2DaysMax = $tempIn2DaysMax;
        $this->tempIn2DaysMin = $tempIn2DaysMin;
        $this->tempIn3DaysMax = $tempIn3DaysMax;
        $this->tempIn3DaysMin = $tempIn3DaysMin;
        $this->tempIn4DaysMax = $tempIn4DaysMax;
        $this->tempIn4DaysMin = $tempIn4DaysMin;
        $this->tempIn5DaysMax = $tempIn5DaysMax;
        $this->tempIn5DaysMin = $tempIn5DaysMin;
        $this->weatherIn1Days = $weatherIn1Days;
        $this->weatherIn2Days = $weatherIn2Days;
        $this->weatherIn3Days = $weatherIn3Days;
        $this->weatherIn4Days = $weatherIn4Days;
        $this->weatherIn5Days = $weatherIn5Days;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): WeatherForecast
    {
        $this->location = $location;
        return $this;
    }

    public function getCurrentTemp(): float
    {
        return $this->CurrentTemp;
    }

    public function setCurrentTemp(float $CurrentTemp): WeatherForecast
    {
        $this->CurrentTemp = $CurrentTemp;
        return $this;
    }

    public function getCurrentWeather(): WeatherType
    {
        return $this->currentWeather;
    }

    public function setCurrentWeather(WeatherType $currentWeather): WeatherForecast
    {
        $this->currentWeather = $currentWeather;
        return $this;
    }

    public function getTempTodayMax(): float
    {
        return $this->tempTodayMax;
    }

    public function setTempTodayMax(float $tempTodayMax): WeatherForecast
    {
        $this->tempTodayMax = $tempTodayMax;
        return $this;
    }

    public function getTempTodayMin(): float
    {
        return $this->tempTodayMin;
    }

    public function setTempTodayMin(float $tempTodayMin): WeatherForecast
    {
        $this->tempTodayMin = $tempTodayMin;
        return $this;
    }

    public function getTempIn1DaysMax(): float
    {
        return $this->tempIn1DaysMax;
    }

    public function setTempIn1DaysMax(float $tempIn1DaysMax): WeatherForecast
    {
        $this->tempIn1DaysMax = $tempIn1DaysMax;
        return $this;
    }

    public function getTempIn1DaysMin(): float
    {
        return $this->tempIn1DaysMin;
    }

    public function setTempIn1DaysMin(float $tempIn1DaysMin): WeatherForecast
    {
        $this->tempIn1DaysMin = $tempIn1DaysMin;
        return $this;
    }

    public function getTempIn2DaysMax(): float
    {
        return $this->tempIn2DaysMax;
    }

    public function setTempIn2DaysMax(float $tempIn2DaysMax): WeatherForecast
    {
        $this->tempIn2DaysMax = $tempIn2DaysMax;
        return $this;
    }

    public function getTempIn2DaysMin(): float
    {
        return $this->tempIn2DaysMin;
    }

    public function setTempIn2DaysMin(float $tempIn2DaysMin): WeatherForecast
    {
        $this->tempIn2DaysMin = $tempIn2DaysMin;
        return $this;
    }

    public function getTempIn3DaysMax(): float
    {
        return $this->tempIn3DaysMax;
    }

    public function setTempIn3DaysMax(float $tempIn3DaysMax): WeatherForecast
    {
        $this->tempIn3DaysMax = $tempIn3DaysMax;
        return $this;
    }

    public function getTempIn3DaysMin(): float
    {
        return $this->tempIn3DaysMin;
    }

    public function setTempIn3DaysMin(float $tempIn3DaysMin): WeatherForecast
    {
        $this->tempIn3DaysMin = $tempIn3DaysMin;
        return $this;
    }

    public function getTempIn4DaysMax(): float
    {
        return $this->tempIn4DaysMax;
    }

    public function setTempIn4DaysMax(float $tempIn4DaysMax): WeatherForecast
    {
        $this->tempIn4DaysMax = $tempIn4DaysMax;
        return $this;
    }

    public function getTempIn4DaysMin(): float
    {
        return $this->tempIn4DaysMin;
    }

    public function setTempIn4DaysMin(float $tempIn4DaysMin): WeatherForecast
    {
        $this->tempIn4DaysMin = $tempIn4DaysMin;
        return $this;
    }

    public function getTempIn5DaysMax(): float
    {
        return $this->tempIn5DaysMax;
    }

    public function setTempIn5DaysMax(float $tempIn5DaysMax): WeatherForecast
    {
        $this->tempIn5DaysMax = $tempIn5DaysMax;
        return $this;
    }

    public function getTempIn5DaysMin(): float
    {
        return $this->tempIn5DaysMin;
    }

    public function setTempIn5DaysMin(float $tempIn5DaysMin): WeatherForecast
    {
        $this->tempIn5DaysMin = $tempIn5DaysMin;
        return $this;
    }

    public function getWeatherIn1Days(): WeatherType
    {
        return $this->weatherIn1Days;
    }

    public function setWeatherIn1Days(WeatherType $weatherIn1Days): WeatherForecast
    {
        $this->weatherIn1Days = $weatherIn1Days;
        return $this;
    }

    public function getWeatherIn2Days(): WeatherType
    {
        return $this->weatherIn2Days;
    }

    public function setWeatherIn2Days(WeatherType $weatherIn2Days): WeatherForecast
    {
        $this->weatherIn2Days = $weatherIn2Days;
        return $this;
    }

    public function getWeatherIn3Days(): WeatherType
    {
        return $this->weatherIn3Days;
    }

    public function setWeatherIn3Days(WeatherType $weatherIn3Days): WeatherForecast
    {
        $this->weatherIn3Days = $weatherIn3Days;
        return $this;
    }

    public function getWeatherIn4Days(): WeatherType
    {
        return $this->weatherIn4Days;
    }

    public function setWeatherIn4Days(WeatherType $weatherIn4Days): WeatherForecast
    {
        $this->weatherIn4Days = $weatherIn4Days;
        return $this;
    }

    public function getWeatherIn5Days(): WeatherType
    {
        return $this->weatherIn5Days;
    }

    public function setWeatherIn5Days(WeatherType $weatherIn5Days): WeatherForecast
    {
        $this->weatherIn5Days = $weatherIn5Days;
        return $this;
    }
}
