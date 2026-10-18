<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\FeedOverviewItemResponse;
use App\Feed\Dto\Response\FeedOverviewResponse;
use App\Feed\Repository\UserFeedRepository;
use App\Shared\Security\CurrentUser;
use Override;

/** @implements ProviderInterface<FeedOverviewResponse> */
final readonly class FeedOverviewProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserFeedRepository $repository,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): FeedOverviewResponse
    {
        $user = $this->currentUser->get();
        $counts = $this->repository->countItemsPerFeedForUser($user);
        $items = [];

        foreach ($this->repository->findForUser($user) as $subscription) {
            $feed = $subscription->getFeed();
            $feedUuid = $feed->getUuid()->toRfc4122();
            $items[] = new FeedOverviewItemResponse(
                uuid: $feedUuid,
                name: $feed->getName(),
                color: $subscription->getColor() ?? $feed->getColor(),
                icon: $subscription->getIcon() ?? '',
                autoPin: $subscription->isAutoPin(),
                itemCount: $counts[$feedUuid] ?? 0,
            );
        }

        return new FeedOverviewResponse($items);
    }
}
