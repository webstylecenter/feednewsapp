<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class DashboardStatisticsResponse
{
    /**
     * @param list<DashboardStatisticsCategoryResponse> $categories
     * @param list<DashboardStatisticsDayResponse> $daily
     * @param list<DashboardStatisticsFeedResponse> $topFeeds
     * @param list<DashboardStatisticsFeedResponse> $topFeeds7Days
     */
    public function __construct(
        public DashboardStatisticsTotalsResponse $totals,
        public array $categories,
        public array $daily,
        public array $topFeeds,
        public array $topFeeds7Days,
    ) {
    }
}
