<?php

declare(strict_types=1);

namespace App\Feed\Mapper;

use App\Feed\Dto\Response\FeedSubscriptionResponse;
use App\Feed\Dto\Response\FeedSummaryResponse;
use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedError;
use App\Feed\Entity\FeedUser;

final readonly class FeedSettingsMapper
{
    public function toFeedResponse(
        Feed $feed,
        ?string $lastItemAt = null,
        ?FeedError $latestError = null,
    ): FeedSummaryResponse {
        [$httpErrorStatus, $httpErrorMessage] = $this->currentHttpError($feed, $latestError);

        return new FeedSummaryResponse(
            uuid: $feed->getUuid()->toRfc4122(),
            name: $feed->getName(),
            url: $feed->getUrl(),
            color: $feed->getColor(),
            category: $feed->getCategory()?->getName(),
            lastItemAt: $lastItemAt,
            httpErrorStatus: $httpErrorStatus,
            httpErrorMessage: $httpErrorMessage,
        );
    }

    public function toSubscriptionResponse(
        FeedUser $subscription,
        ?string $lastItemAt = null,
        ?FeedError $latestError = null,
    ): FeedSubscriptionResponse {
        $feed = $subscription->getFeed();

        return new FeedSubscriptionResponse(
            uuid: $subscription->getUuid()->toRfc4122(),
            feed: $this->toFeedResponse($feed, $lastItemAt, $latestError),
            color: $subscription->getColor() ?? $feed->getColor(),
            icon: $subscription->getIcon() ?? '',
            autoPin: $subscription->isAutoPin(),
        );
    }

    /** @return array{0: ?int, 1: ?string} */
    private function currentHttpError(Feed $feed, ?FeedError $error): array
    {
        if (
            $error === null
            || str_contains(strtolower($feed->getUrl()), 'youtube.com/')
            || $error->getUpdatedAt() <= $feed->getUpdatedAt()
        ) {
            return [null, null];
        }

        $message = $error->getException();
        $status = match (true) {
            str_contains($message, 'HTTP 403') => 403,
            str_contains($message, 'HTTP 404') => 404,
            str_contains($message, 'HTTP 500') => 500,
            default => null,
        };

        return $status === null ? [null, null] : [$status, $message];
    }
}
