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
        $items = [];
        foreach ($this->repository->findForUser($this->currentUser->get()) as $subscription) {
            $id = $subscription->getId();
            if ($id === null) {
                continue;
            }
            $feed = $subscription->getFeed();
            $items[] = new FeedOverviewItemResponse(
                id: $id,
                name: $feed->getName(),
                color: $subscription->getColor() ?? $feed->getColor(),
                icon: $subscription->getIcon() ?? '',
                autoPin: $subscription->isAutoPin(),
                itemCount: $subscription->getItems()->count(),
            );
        }

        return new FeedOverviewResponse($items);
    }
}
