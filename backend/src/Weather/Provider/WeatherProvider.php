<?php

declare(strict_types=1);

namespace App\Weather\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Weather\Dto\Response\WeatherResponse;
use App\Weather\Mapper\WeatherMapper;
use App\Weather\Service\UserWeatherService;
use Override;

/** @implements ProviderInterface<WeatherResponse> */
final readonly class WeatherProvider implements ProviderInterface
{
    public function __construct(
        private UserWeatherService $service,
        private WeatherMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WeatherResponse
    {
        return new WeatherResponse($this->mapper->toResponse($this->service->forecast()));
    }
}
