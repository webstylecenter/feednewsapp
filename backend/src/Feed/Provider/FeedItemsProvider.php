<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\FeedItemsPageResponse;
use App\Feed\Mapper\FeedItemMapper;
use App\Feed\Service\FeedService;
use App\Shared\Security\CurrentUser;
use Override;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<FeedItemsPageResponse> */
final readonly class FeedItemsProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): FeedItemsPageResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $page = max(0, $request?->query->getInt('page') ?? 0);
        $limit = max(1, min(100, $request?->query->getInt('limit', 50) ?? 50));
        $items = $this->feedService->recent($this->currentUser->get(), $page, $limit);

        return new FeedItemsPageResponse(
            items: $this->mapper->toResponses($items),
            nextPage: count($items) === $limit ? $page + 1 : null,
        );
    }
}
