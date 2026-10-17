<?php

declare(strict_types=1);

namespace App\Weather\Service;

use App\Shared\Http\NativeHttpClient;
use App\Weather\Entity\WeatherForecast;
use App\Weather\Entity\WeatherForecastDay;
use App\Weather\Enum\WeatherType;
use App\Weather\Repository\WeatherForecastRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use RuntimeException;

final readonly class WeatherService
{
    private const string DEFAULT_LOCATION = 'Eindhoven,NL';
    private const float DEFAULT_LATITUDE = 51.4416;
    private const float DEFAULT_LONGITUDE = 5.4697;

    public function __construct(
        private NativeHttpClient $httpClient,
        private WeatherForecastRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function current(): ?WeatherForecast
    {
        $location = $this->envString('WEATHER_LOCATION', self::DEFAULT_LOCATION);
        return $this->repository->findOneBy(['location' => $location]);
    }

    public function update(): WeatherForecast
    {
        $location = $this->envString('WEATHER_LOCATION', self::DEFAULT_LOCATION);
        $latitude = $this->envFloat('WEATHER_LATITUDE', self::DEFAULT_LATITUDE);
        $longitude = $this->envFloat('WEATHER_LONGITUDE', self::DEFAULT_LONGITUDE);
        $url = sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current=temperature_2m,weather_code&daily=weather_code,temperature_2m_max,temperature_2m_min&forecast_days=6&timezone=auto',
            rawurlencode((string) $latitude),
            rawurlencode((string) $longitude),
        );

        $response = $this->httpClient->get($url, ['Accept' => 'application/json']);
        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new RuntimeException(sprintf('Weather API returned HTTP %d.', $response->statusCode));
        }

        try {
            $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Weather API returned invalid JSON.', previous: $exception);
        }
        if (!is_array($data)) {
            throw new RuntimeException('Weather API returned an invalid response.');
        }

        $current = $data['current'] ?? null;
        $daily = $data['daily'] ?? null;
        if (!is_array($current) || !is_array($daily)) {
            throw new RuntimeException('Weather API response did not contain forecast data.');
        }

        $currentTemp = $this->number($current, 'temperature_2m');
        $currentCode = $this->integer($current, 'weather_code');
        $forecast = $this->repository->findOneBy(['location' => $location]);
        if (!$forecast instanceof WeatherForecast) {
            $forecast = new WeatherForecast($location, $currentTemp, $this->mapCode($currentCode));
            $this->entityManager->persist($forecast);
        } else {
            $forecast->setCurrentTemp($currentTemp);
            $forecast->setCurrentWeather($this->mapCode($currentCode));
        }

        foreach ($forecast->getForecastDays()->toArray() as $day) {
            $forecast->removeForecastDay($day);
            $this->entityManager->remove($day);
        }

        $maxTemperatures = $this->numericList($daily, 'temperature_2m_max');
        $minTemperatures = $this->numericList($daily, 'temperature_2m_min');
        $codes = $this->integerList($daily, 'weather_code');
        $length = min(count($maxTemperatures), count($minTemperatures), count($codes), 6);
        for ($index = 0; $index < $length; ++$index) {
            $day = new WeatherForecastDay(
                $forecast,
                $index,
                $maxTemperatures[$index],
                $minTemperatures[$index],
                $this->mapCode($codes[$index]),
            );
            $forecast->addForecastDay($day);
            $this->entityManager->persist($day);
        }

        $this->entityManager->flush();
        return $forecast;
    }

    /** @param array<string, mixed> $data */
    private function number(array $data, string $key): float
    {
        $value = $data[$key] ?? null;
        if (!is_int($value) && !is_float($value)) {
            throw new RuntimeException(sprintf('Weather field "%s" is missing.', $key));
        }
        return (float) $value;
    }

    /** @param array<string, mixed> $data */
    private function integer(array $data, string $key): int
    {
        $value = $data[$key] ?? null;
        if (!is_int($value) && !is_float($value)) {
            throw new RuntimeException(sprintf('Weather field "%s" is missing.', $key));
        }
        return (int) $value;
    }

    /** @param array<string, mixed> $data
     *  @return list<float>
     */
    private function numericList(array $data, string $key): array
    {
        $values = $data[$key] ?? null;
        if (!is_array($values)) {
            throw new RuntimeException(sprintf('Weather field "%s" is missing.', $key));
        }

        $result = [];
        foreach ($values as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw new RuntimeException(sprintf('Weather field "%s" contains invalid data.', $key));
            }
            $result[] = (float) $value;
        }
        return $result;
    }

    /** @param array<string, mixed> $data
     *  @return list<int>
     */
    private function integerList(array $data, string $key): array
    {
        $values = $data[$key] ?? null;
        if (!is_array($values)) {
            throw new RuntimeException(sprintf('Weather field "%s" is missing.', $key));
        }

        $result = [];
        foreach ($values as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw new RuntimeException(sprintf('Weather field "%s" contains invalid data.', $key));
            }
            $result[] = (int) $value;
        }
        return $result;
    }

    private function mapCode(int $code): WeatherType
    {
        return match (true) {
            $code === 0 => WeatherType::CLEAR,
            in_array($code, [1, 2], true) => WeatherType::PARTLY_CLOUD,
            $code === 3, in_array($code, [45, 48], true) => WeatherType::CLOUD,
            in_array($code, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82], true) => WeatherType::RAIN,
            in_array($code, [71, 73, 75, 77, 85, 86], true) => WeatherType::SNOW,
            in_array($code, [95, 96, 99], true) => WeatherType::THUNDER,
            default => WeatherType::UNKNOWN,
        };
    }

    private function envString(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? null;
        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    private function envFloat(string $key, float $default): float
    {
        $value = $_ENV[$key] ?? null;
        return is_numeric($value) ? (float) $value : $default;
    }
}
