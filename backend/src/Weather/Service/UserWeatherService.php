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

    public function forecast(): WeatherForecast
    {
        $user = $this->currentUser->get();
        $location = $this->settingRepository->findForUserAndKey($user, 'weather_location')?->getValue();

        return $this->weatherService->forecast($location);
    }
}
