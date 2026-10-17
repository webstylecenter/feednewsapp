<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\CategoryResponse;
use App\Feed\Dto\Response\SettingsResponse;
use App\Feed\Mapper\FeedSettingsMapper;
use App\Feed\Repository\FeedCategoryRepository;
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
        $followedFeedIds = [];
        $subscriptions = [];
        foreach ($this->userFeedRepository->findForUser($user) as $subscription) {
            $feedId = $subscription->getFeed()->getId();
            if ($feedId === null || $subscription->getId() === null) {
                continue;
            }
            $followedFeedIds[$feedId] = true;
            $subscriptions[] = $this->mapper->toSubscriptionResponse($subscription);
        }

        $availableFeeds = [];
        foreach ($this->feedRepository->findAllOrdered() as $feed) {
            $id = $feed->getId();
            if ($id === null || isset($followedFeedIds[$id])) {
                continue;
            }
            $availableFeeds[] = $this->mapper->toFeedResponse($feed);
        }

        $categories = [];
        foreach ($this->categoryRepository->findAllOrdered() as $category) {
            $id = $category->getId();
            if ($id !== null) {
                $categories[] = new CategoryResponse($id, $category->getName());
            }
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
