<?php

declare(strict_types=1);

namespace App\Weather\Entity;

use App\Weather\Enum\WeatherType;
use App\Weather\Repository\WeatherForecastRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private string $location;

    #[ORM\Column(type: Types::FLOAT)]
    private float $currentTemp;

    #[ORM\Column(type: Types::STRING, enumType: WeatherType::class)]
    private WeatherType $currentWeather;

    /**
     * @var Collection<int, WeatherForecastDay>
     */
    #[ORM\OneToMany(
        targetEntity: WeatherForecastDay::class,
        mappedBy: 'forecast',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['dayOffset' => 'ASC'])]
    private Collection $forecastDays;

    public function __construct(
        string $location,
        float $currentTemp,
        WeatherType $currentWeather
    ) {
        $this->forecastDays = new ArrayCollection();
        $this->location = $location;
        $this->currentTemp = $currentTemp;
        $this->currentWeather = $currentWeather;
    }

    public function getId(): ?int
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
        return $this->currentTemp;
    }

    public function setCurrentTemp(float $currentTemp): WeatherForecast
    {
        $this->currentTemp = $currentTemp;
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

    /**
     * @return Collection<int, WeatherForecastDay>
     */
    public function getForecastDays(): Collection
    {
        return $this->forecastDays;
    }

    public function setForecastDays(WeatherForecastDay ...$days): WeatherForecast
    {
        foreach ($this->forecastDays as $existing) {
            $this->removeForecastDay($existing);
        }
        foreach ($days as $day) {
            $this->addForecastDay($day);
        }
        return $this;
    }

    public function addForecastDay(WeatherForecastDay $day): WeatherForecast
    {
        if (!$this->forecastDays->contains($day)) {
            $this->forecastDays->add($day);
        }

        if ($day->getForecast() !== $this) {
            $day->setForecast($this);
        }

        return $this;
    }

    public function removeForecastDay(WeatherForecastDay $day): WeatherForecast
    {
        if ($this->forecastDays->removeElement($day)) {
            if ($day->getForecast() === $this) {
                $day->setForecast(null);
            }
        }

        return $this;
    }
}
