<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Weather\Service\WeatherPresenter;
use App\Weather\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherController extends AbstractController
{
    #[Route('/api/weather', name: 'api_weather', methods: ['GET'])]
    public function weather(WeatherService $service, WeatherPresenter $presenter): JsonResponse
    {
        $forecast = $service->current();
        if ($forecast === null) {
            $forecast = $service->update();
        }

        return $this->json(['forecast' => $presenter->present($forecast)]);
    }
}
