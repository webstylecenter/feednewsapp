<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

use App\Weather\Dto\Response\WeatherForecastResponse;

final readonly class DashboardResponse
{
    /**
     * @param list<DashboardNoteResponse> $notes
     * @param list<DashboardTodoResponse> $todos
     * @param list<DashboardFeedResponse> $feeds
     */
    public function __construct(
        public array $notes,
        public array $todos,
        public array $feeds,
        public ?WeatherForecastResponse $weather,
    ) {
    }
}
