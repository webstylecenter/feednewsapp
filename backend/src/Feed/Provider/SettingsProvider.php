<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\CategoryResponse;
use App\Feed\Dto\Response\SettingsResponse;
use App\Feed\Entity\Feed;
use App\Feed\Mapper\FeedSettingsMapper;
use App\Feed\Repository\FeedCategoryRepository;
use App\Feed\Repository\FeedErrorRepository;
use App\Feed\Repository\FeedItemRepository;
use App\Feed\Repository\FeedRepository;
use App\Feed\Repository\UserFeedRepository;
use App\Shared\Security\CurrentUser;
use App\User\Repository\UserSettingRepository;
use Override;

/** @implements ProviderInterface<SettingsResponse> */
final readonly class SettingsProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserFeedRepository $userFeedRepository,
        private FeedRepository $feedRepository,
        private FeedCategoryRepository $categoryRepository,
        private UserSettingRepository $settingRepository,
        private FeedItemRepository $feedItemRepository,
        private FeedErrorRepository $feedErrorRepository,
        private FeedSettingsMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SettingsResponse
    {
        $user = $this->currentUser->get();
        $userFeeds = $this->userFeedRepository->findForUser($user);
        $feeds = array_map(
            static fn ($subscription): Feed => $subscription->getFeed(),
            $userFeeds,
        );
        $latestItemAtByFeed = $this->feedItemRepository->findLatestCreatedAtByFeeds($feeds);
        $latestErrorByFeed = $this->feedErrorRepository->findLatestForFeeds($feeds);

        $followedFeedUuids = [];
        $subscriptions = [];
        foreach ($userFeeds as $subscription) {
            $feed = $subscription->getFeed();
            $feedUuid = $feed->getUuid()->toRfc4122();
            $feedId = $feed->getId();
            $followedFeedUuids[$feedUuid] = true;
            $subscriptions[] = $this->mapper->toSubscriptionResponse(
                $subscription,
                $feedId === null ? null : ($latestItemAtByFeed[$feedId] ?? null),
                $feedId === null ? null : ($latestErrorByFeed[$feedId] ?? null),
            );
        }

        $availableFeeds = [];
        foreach ($this->feedRepository->findAllOrdered() as $feed) {
            $uuid = $feed->getUuid()->toRfc4122();
            if (isset($followedFeedUuids[$uuid])) {
                continue;
            }
            $availableFeeds[] = $this->mapper->toFeedResponse($feed);
        }

        $categories = [];
        foreach ($this->categoryRepository->findAllOrdered() as $category) {
            $categories[] = new CategoryResponse(
                $category->getUuid()->toRfc4122(),
                $category->getName(),
            );
        }

        return new SettingsResponse(
            subscriptions: $subscriptions,
            availableFeeds: $availableFeeds,
            categories: $categories,
            preferences: $this->settingRepository->valuesForUser($user),
            hideXFrameNotice: $user->isHideXFrameNotice(),
        );
    }
}
