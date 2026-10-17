<?php

declare(strict_types=1);

namespace App\Feed\Mapper;

use App\Feed\Dto\Response\FeedItemResponse;
use App\Feed\Entity\FeedUserItem;
use LogicException;

final class FeedItemMapper
{
    public function toResponse(FeedUserItem $userItem): FeedItemResponse
    {
        $feedItem = $userItem->getFeedItem();
        $userFeed = $userItem->getUserFeed();
        $feed = $userFeed?->getFeed() ?? $feedItem->getFeed();
        $id = $userItem->getId();
        if ($id === null) {
            throw new LogicException('Cannot map an unpersisted feed user item.');
        }

        $feedName = $feed?->getName() ?? '';
        $description = trim((string) $feedItem->getDescription());
        if ($description === '') {
            $description = $feedName;
        }

        return new FeedItemResponse(
            id: $id,
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
            createdAt: $userItem->getCreatedAt()->format(DATE_ATOM),
            tagId: $userItem->getTag()?->getId(),
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
}
