<?php

declare(strict_types=1);

namespace App\Feed\Mapper;

use App\Feed\Dto\Response\FeedItemResponse;
use App\Feed\Entity\FeedUserItem;
use DateTimeInterface;
use LogicException;

final class FeedItemMapper
{
    public function toResponse(FeedUserItem $userItem): FeedItemResponse
    {
        $feedItem = $userItem->getFeedItem();
        $userFeed = $userItem->getUserFeed();
        $feed = $userFeed?->getFeed() ?? $feedItem->getFeed();

        $feedName = $feed?->getName() ?? '';
        $description = trim((string) $feedItem->getDescription());
        if ($description === '') {
            $description = $feedName;
        }

        return new FeedItemResponse(
            uuid: $userItem->getUuid()->toRfc4122(),
            title: $feedItem->getTitle(),
            description: $description,
            url: $feedItem->getUrl(),
            color: $userFeed?->getColor() ?? $feed?->getColor() ?? '#337dff',
            feedIcon: $userFeed?->getIcon() ?? '',
            feedName: $feedName,
            pinned: $userItem->isPinned(),
            viewed: $userItem->isViewed(),
            openedAt: $userItem->getOpenedAt()?->format(DATE_ATOM),
            createdAt: self::formatTimestamp($userItem->getCreatedAt()),
            tagUuid: $userItem->getTag()?->getUuid()->toRfc4122(),
        );
    }

    /**
     * @param list<FeedUserItem> $items
     * @return list<FeedItemResponse>
     */
    public function toResponses(array $items): array
    {
        return array_map($this->toResponse(...), $items);
    }
    private static function formatTimestamp(?DateTimeInterface $dateTime): string
    {
        if ($dateTime === null) {
            throw new LogicException('Persisted feed item is missing a creation timestamp.');
        }

        return $dateTime->format(DATE_ATOM);
    }
}
