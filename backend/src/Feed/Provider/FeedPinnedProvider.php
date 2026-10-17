<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\FeedItemsResponse;
use App\Feed\Mapper\FeedItemMapper;
use App\Feed\Service\FeedService;
use App\Shared\Security\CurrentUser;
use Override;

/** @implements ProviderInterface<FeedItemsResponse> */
final readonly class FeedPinnedProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedService $feedService,
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
            $this->mapper->toResponses($this->feedService->pinned($this->currentUser->get())),
        );
    }
}
