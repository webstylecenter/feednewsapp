<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class SettingsResponse
{
    /**
     * @param list<FeedSubscriptionResponse> $subscriptions
     * @param list<FeedSummaryResponse> $availableFeeds
     * @param list<CategoryResponse> $categories
     * @param array<string, string|null> $preferences
     */
    public function __construct(
        public array $subscriptions,
        public array $availableFeeds,
        public array $categories,
        public array $preferences,
        public bool $hideXFrameNotice,
    ) {
    }
}
