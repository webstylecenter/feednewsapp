<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Entity\FeedUserItem;
use LogicException;

final class FeedItemPresenter
{
    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     title: string,
     *     description: string,
     *     url: string,
     *     color: string,
     *     feedIcon: string,
     *     feedName: string,
     *     pinned: bool,
     *     viewed: bool,
     *     openedAt: string|null,
     *     createdAt: string,
     *     tagId: int|null
     * }
     */
    public function present(FeedUserItem $userItem): array
    {
        $feedItem = $userItem->getFeedItem();
        $userFeed = $userItem->getUserFeed();
        $feed = $userFeed?->getFeed() ?? $feedItem->getFeed();
        $id = $userItem->getId();
        if ($id === null) {
            throw new LogicException('Cannot present an unpersisted feed user item.');
        }

        $feedName = $feed?->getName() ?? '';
        $description = trim((string) $feedItem->getDescription());
        if ($description === '') {
            $description = $feedName;
        }

        return [
            'id' => $id,
            'uuid' => $userItem->getUuid()->toRfc4122(),
            'title' => $feedItem->getTitle(),
            'description' => $description,
            'url' => $feedItem->getUrl(),
            'color' => $userFeed?->getColor() ?? $feed?->getColor() ?? '#337dff',
            'feedIcon' => $userFeed?->getIcon() ?? '',
            'feedName' => $feedName,
            'pinned' => $userItem->isPinned(),
            'viewed' => $userItem->isViewed(),
            'openedAt' => $userItem->getOpenedAt()?->format(DATE_ATOM),
            'createdAt' => $userItem->getCreatedAt()->format(DATE_ATOM),
            'tagId' => $userItem->getTag()?->getId(),
        ];
    }

    /** @param list<FeedUserItem> $items
     *  @return list<array<string, mixed>>
     */
    public function presentMany(array $items): array
    {
        return array_map(fn (FeedUserItem $item): array => $this->present($item), $items);
    }
}
