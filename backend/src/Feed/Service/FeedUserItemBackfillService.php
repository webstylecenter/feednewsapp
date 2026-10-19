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
            $missingSubscriptions = [];
            foreach ($subscriptions as $subscription) {
                if (
                    $this->userFeedItemRepository->findOneBy([
                        'user' => $subscription->getUser(),
                        'feedItem' => $feedItem,
                    ]) instanceof FeedUserItem
                ) {
                    continue;
                }

                $missingSubscriptions[] = $subscription;
            }

            if ($missingSubscriptions === []) {
                continue;
            }

            if ($isYouTubeFeed && $this->youTubeVideoFilter->isShort($feedItem->getUrl())) {
                continue;
            }

            foreach ($missingSubscriptions as $subscription) {
                $userItem = new FeedUserItem($subscription->getUser(), $feedItem, $subscription, null);
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
