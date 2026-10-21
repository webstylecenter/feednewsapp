<?php

declare(strict_types=1);

namespace App\Weather\Service;

use App\Shared\Http\NativeHttpClient;
use App\Weather\Entity\WeatherForecast;
use App\Weather\Entity\WeatherForecastDay;
use App\Weather\Enum\WeatherType;
use App\Weather\Repository\WeatherForecastRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use RuntimeException;

final readonly class WeatherService
{
    private const string DEFAULT_LOCATION = 'Eindhoven,NL';
    private const float DEFAULT_LATITUDE = 51.4416;
    private const float DEFAULT_LONGITUDE = 5.4697;
    private const string CACHE_MAX_AGE = '-35 minutes';

    public function __construct(
        private NativeHttpClient $httpClient,
        private WeatherForecastRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function current(?string $location = null): ?WeatherForecast
    {
        return $this->repository->findOneBy(['location' => $this->location($location)]);
    }

    public function forecast(?string $location = null): WeatherForecast
    {
        $location = $this->location($location);
        $forecast = $this->current($location);

        if (
            $forecast instanceof WeatherForecast
            && $forecast->getUpdatedAt() >= new DateTimeImmutable(self::CACHE_MAX_AGE)
        ) {
            return $forecast;
        }

        return $this->update($location);
    }

    public function update(?string $location = null): WeatherForecast
    {
        $location = $this->location($location);
        [$latitude, $longitude] = $this->coordinates($location);
        $url = sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current=temperature_2m,weather_code&daily=weather_code,temperature_2m_max,temperature_2m_min&forecast_days=6&timezone=auto',
            rawurlencode((string) $latitude),
            rawurlencode((string) $longitude),
        );

        $data = $this->jsonResponse($url, 'Weather API');
        $current = $this->stringKeyedArray($data['current'] ?? null, 'current');
        $daily = $this->stringKeyedArray($data['daily'] ?? null, 'daily');

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

    /** @return array{float, float} */
    private function coordinates(string $location): array
    {
        $defaultLocation = $this->envString('WEATHER_LOCATION', self::DEFAULT_LOCATION);
        if ($location === $defaultLocation) {
            return [
                $this->envFloat('WEATHER_LATITUDE', self::DEFAULT_LATITUDE),
                $this->envFloat('WEATHER_LONGITUDE', self::DEFAULT_LONGITUDE),
            ];
        }

        $url = sprintf(
            'https://geocoding-api.open-meteo.com/v1/search?name=%s&count=1&language=en&format=json',
            rawurlencode($location),
        );
        $data = $this->jsonResponse($url, 'Weather location API');
        $results = $data['results'] ?? null;
        if (!is_array($results) || !isset($results[0])) {
            throw new RuntimeException(sprintf('Weather location "%s" could not be found.', $location));
        }

        $result = $this->stringKeyedArray($results[0], 'results[0]');

        return [
            $this->number($result, 'latitude'),
            $this->number($result, 'longitude'),
        ];
    }

    /** @return array<string, mixed> */
    private function jsonResponse(string $url, string $service): array
    {
        $response = $this->httpClient->get($url, ['Accept' => 'application/json']);
        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new RuntimeException(sprintf('%s returned HTTP %d.', $service, $response->statusCode));
        }

        try {
            $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('%s returned invalid JSON.', $service), previous: $exception);
        }
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('%s returned an invalid response.', $service));
        }

        return $this->stringKeyedArray($data, $service);
    }

    /** @return array<string, mixed> */
    private function stringKeyedArray(mixed $value, string $field): array
    {
        if (!is_array($value)) {
            throw new RuntimeException(sprintf('Weather field "%s" is missing.', $field));
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new RuntimeException(sprintf('Weather field "%s" contains invalid data.', $field));
            }

            $result[$key] = $item;
        }

        return $result;
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

    private function location(?string $location): string
    {
        $location = trim((string) $location);

        return $location !== '' ? $location : $this->envString('WEATHER_LOCATION', self::DEFAULT_LOCATION);
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
