<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Repository\UserFeedItemRepository;
use App\Shared\Security\CurrentUser;
use App\User\Dto\Response\DashboardStatisticsCategoryResponse;
use App\User\Dto\Response\DashboardStatisticsDayResponse;
use App\User\Dto\Response\DashboardStatisticsFeedResponse;
use App\User\Dto\Response\DashboardStatisticsResponse;
use App\User\Dto\Response\DashboardStatisticsTotalsResponse;
use DateTimeImmutable;
use Override;

/** @implements ProviderInterface<DashboardStatisticsResponse> */
final readonly class DashboardStatisticsProvider implements ProviderInterface
{
    private const int DAILY_DAYS = 14;
    private const int TOP_FEED_DAYS = 30;
    private const int TOP_FEED_LIMIT = 5;

    public function __construct(
        private CurrentUser $currentUser,
        private UserFeedItemRepository $feedItemRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): DashboardStatisticsResponse {
        $user = $this->currentUser->get();
        $totals = $this->feedItemRepository->dashboardTotalsForUser($user);
        $today = new DateTimeImmutable('today');
        $dailyStart = $today->modify('-' . (self::DAILY_DAYS - 1) . ' days');
        $dailyCounts = $this->feedItemRepository->countCreatedByDayForUser($user, $dailyStart);
        $daily = [];

        for ($dayOffset = 0; $dayOffset < self::DAILY_DAYS; $dayOffset++) {
            $date = $dailyStart->modify('+' . $dayOffset . ' days');
            $key = $date->format('Y-m-d');
            $daily[] = new DashboardStatisticsDayResponse(
                date: $key,
                count: $dailyCounts[$key] ?? 0,
            );
        }

        $categories = array_map(
            static fn (array $row): DashboardStatisticsCategoryResponse => new DashboardStatisticsCategoryResponse(
                name: $row['name'],
                count: $row['count'],
            ),
            $this->feedItemRepository->countByCategoryForUser($user),
        );

        $topFeedStart = $today->modify('-' . (self::TOP_FEED_DAYS - 1) . ' days');
        $topFeeds = array_map(
            static fn (array $row): DashboardStatisticsFeedResponse => new DashboardStatisticsFeedResponse(
                name: $row['name'],
                count: $row['count'],
            ),
            $this->feedItemRepository->topFeedsForUser($user, $topFeedStart, self::TOP_FEED_LIMIT),
        );

        return new DashboardStatisticsResponse(
            totals: new DashboardStatisticsTotalsResponse(
                items: $totals['items'],
                unread: $totals['unread'],
                opened: $totals['opened'],
                pinned: $totals['pinned'],
            ),
            categories: $categories,
            daily: $daily,
            topFeeds: $topFeeds,
        );
    }
}
