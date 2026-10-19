<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUserItem;
use App\Feed\Repository\FeedItemRepository;
use App\Feed\Repository\UserFeedItemRepository;
use App\Feed\Repository\UserFeedRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FeedUserItemBackfillService
{
    private const int MAX_ITEMS_PER_FEED = 100;

    public function __construct(
        private FeedItemRepository $feedItemRepository,
        private UserFeedRepository $userFeedRepository,
        private UserFeedItemRepository $userFeedItemRepository,
        private YouTubeVideoFilter $youTubeVideoFilter,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function repair(Feed $feed): int
    {
        $subscriptions = $this->userFeedRepository->findForFeed($feed);
        if ($subscriptions === []) {
            return 0;
        }

        $created = 0;
        $isYouTubeFeed = $this->youTubeVideoFilter->isYouTubeFeed($feed->getUrl());

        foreach ($this->feedItemRepository->findLatestForFeed($feed, self::MAX_ITEMS_PER_FEED) as $feedItem) {
            if ($isYouTubeFeed && $this->youTubeVideoFilter->isShort($feedItem->getUrl())) {
                continue;
            }

            foreach ($subscriptions as $subscription) {
                $user = $subscription->getUser();
                if ($this->userFeedItemRepository->findOneBy([
                    'user' => $user,
                    'feedItem' => $feedItem,
                ]) instanceof FeedUserItem) {
                    continue;
                }

                $userItem = new FeedUserItem($user, $feedItem, $subscription, null);
                $userItem->setPinned($subscription->isAutoPin());
                $subscription->addUserFeedItem($userItem);
                $this->entityManager->persist($userItem);
                ++$created;
            }
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $created;
    }
}
