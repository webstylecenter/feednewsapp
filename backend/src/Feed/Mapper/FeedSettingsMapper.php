<?php

declare(strict_types=1);

namespace App\Feed\Mapper;

use App\Feed\Dto\Response\FeedSubscriptionResponse;
use App\Feed\Dto\Response\FeedSummaryResponse;
use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;
use LogicException;

final class FeedSettingsMapper
{
    public function toFeedResponse(Feed $feed): FeedSummaryResponse
    {
        $id = $feed->getId();
        if ($id === null) {
            throw new LogicException('Cannot map an unpersisted feed.');
        }

        return new FeedSummaryResponse(
            id: $id,
            name: $feed->getName(),
            url: $feed->getUrl(),
            color: $feed->getColor(),
            category: $feed->getCategory()?->getName(),
        );
    }

    public function toSubscriptionResponse(FeedUser $subscription): FeedSubscriptionResponse
    {
        $id = $subscription->getId();
        if ($id === null) {
            throw new LogicException('Cannot map an unpersisted subscription.');
        }

        return new FeedSubscriptionResponse(
            id: $id,
            feed: $this->toFeedResponse($subscription->getFeed()),
            color: $subscription->getColor() ?? $subscription->getFeed()->getColor(),
            icon: $subscription->getIcon() ?? '',
            autoPin: $subscription->isAutoPin(),
        );
    }
}
