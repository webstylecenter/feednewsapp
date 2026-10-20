<?php

declare(strict_types=1);

namespace App\Feed\Mapper;

use App\Feed\Dto\Response\FeedSubscriptionResponse;
use App\Feed\Dto\Response\FeedSummaryResponse;
use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;
use App\Feed\Repository\FeedErrorRepository;
use App\Feed\Repository\FeedItemRepository;

final readonly class FeedSettingsMapper
{
    public function __construct(
        private ?FeedItemRepository $feedItemRepository = null,
        private ?FeedErrorRepository $feedErrorRepository = null,
    ) {
    }

    public function toFeedResponse(Feed $feed): FeedSummaryResponse
    {
        $latestItems = $this->feedItemRepository?->findLatestForFeed($feed, 1) ?? [];
        $latestItem = $latestItems[0] ?? null;
        [$httpErrorStatus, $httpErrorMessage] = $this->currentHttpError($feed);

        return new FeedSummaryResponse(
            uuid: $feed->getUuid()->toRfc4122(),
            name: $feed->getName(),
            url: $feed->getUrl(),
            color: $feed->getColor(),
            category: $feed->getCategory()?->getName(),
            lastItemAt: $latestItem?->getCreatedAt()->format(DATE_ATOM),
            httpErrorStatus: $httpErrorStatus,
            httpErrorMessage: $httpErrorMessage,
        );
    }

    public function toSubscriptionResponse(FeedUser $subscription): FeedSubscriptionResponse
    {
        $feed = $subscription->getFeed();

        return new FeedSubscriptionResponse(
            uuid: $subscription->getUuid()->toRfc4122(),
            feed: $this->toFeedResponse($feed),
            color: $subscription->getColor() ?? $feed->getColor(),
            icon: $subscription->getIcon() ?? '',
            autoPin: $subscription->isAutoPin(),
        );
    }

    /** @return array{0: ?int, 1: ?string} */
    private function currentHttpError(Feed $feed): array
    {
        if (
            $this->feedErrorRepository === null
            || str_contains(strtolower($feed->getUrl()), 'youtube.com/')
        ) {
            return [null, null];
        }

        $error = $this->feedErrorRepository->findLatestForFeed($feed);
        if ($error === null || $error->getUpdatedAt() <= $feed->getUpdatedAt()) {
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
