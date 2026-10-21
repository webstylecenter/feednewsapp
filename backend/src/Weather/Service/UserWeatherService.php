<?php

declare(strict_types=1);

namespace App\Weather\Service;

use App\Shared\Security\CurrentUser;
use App\User\Repository\UserSettingRepository;
use App\Weather\Entity\WeatherForecast;

final readonly class UserWeatherService
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserSettingRepository $settingRepository,
        private WeatherService $weatherService,
    ) {
    }

    public function current(): ?WeatherForecast
    {
        return $this->weatherService->current($this->location());
    }

    public function forecast(): WeatherForecast
    {
        return $this->weatherService->forecast($this->location());
    }

    private function location(): ?string
    {
        $user = $this->currentUser->get();

        return $this->settingRepository->findForUserAndKey($user, 'weather_location')?->getValue();
    }
}
