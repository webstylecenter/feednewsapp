<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\FeedItemsResponse;
use App\Feed\Mapper\FeedItemMapper;
use App\Feed\Repository\UserFeedItemRepository;
use App\Shared\Security\CurrentUser;
use Override;

/** @implements ProviderInterface<FeedItemsResponse> */
final readonly class FeedHistoryProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserFeedItemRepository $repository,
        private FeedItemMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): FeedItemsResponse
    {
        return new FeedItemsResponse(
            $this->mapper->toResponses($this->repository->findOpenedForUser($this->currentUser->get())),
        );
    }
}
