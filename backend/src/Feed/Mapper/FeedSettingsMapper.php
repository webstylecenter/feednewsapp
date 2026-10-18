<?php

declare(strict_types=1);

namespace App\Feed\Mapper;

use App\Feed\Dto\Response\FeedSubscriptionResponse;
use App\Feed\Dto\Response\FeedSummaryResponse;
use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;

final class FeedSettingsMapper
{
    public function toFeedResponse(Feed $feed): FeedSummaryResponse
    {
        return new FeedSummaryResponse(
            uuid: $feed->getUuid()->toRfc4122(),
            name: $feed->getName(),
            url: $feed->getUrl(),
            color: $feed->getColor(),
            category: $feed->getCategory()?->getName(),
        );
    }

    public function toSubscriptionResponse(FeedUser $subscription): FeedSubscriptionResponse
    {
        $feed = $subscription->getFeed();

        return new FeedSubscriptionResponse(
            uuid: $feed->getUuid()->toRfc4122(),
            feed: $this->toFeedResponse($feed),
            color: $subscription->getColor() ?? $feed->getColor(),
            icon: $subscription->getIcon() ?? '',
            autoPin: $subscription->isAutoPin(),
        );
    }
}
