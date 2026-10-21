<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\FeedItemsPageResponse;
use App\Feed\Entity\FeedUser;
use App\Feed\Mapper\FeedItemMapper;
use App\Feed\Repository\UserFeedRepository;
use App\Feed\Service\FeedService;
use App\Shared\Security\CurrentUser;
use Override;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<FeedItemsPageResponse> */
final readonly class FeedItemsProvider implements ProviderInterface
{
    private const int INITIAL_PINNED_LIMIT = 3;

    public function __construct(
        private RequestStack $requestStack,
        private CurrentUser $currentUser,
        private UserFeedRepository $userFeedRepository,
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
        $user = $this->currentUser->get();
        $source = trim($request?->query->getString('feed') ?? '');
        $manualOnly = $source === 'manual';
        $feedUuid = null;
        if ($source !== '' && !$manualOnly) {
            if (!Uuid::isValid($source)) {
                throw new BadRequestHttpException('Invalid feed filter.');
            }

            $subscription = $this->userFeedRepository->findOwnedByUuid($user, $source);
            if (!$subscription instanceof FeedUser) {
                throw new BadRequestHttpException('Unknown feed filter.');
            }
            $feedUuid = $subscription->getFeed()->getUuid()->toRfc4122();
        }

        $items = $this->feedService->recent($user, $page, $limit, $feedUuid, $manualOnly);

        $pinnedItems = [];
        $pinnedCount = 0;
        if ($page === 0) {
            $pinnedItems = $this->feedService->pinned(
                $user,
                self::INITIAL_PINNED_LIMIT,
                $feedUuid,
                $manualOnly,
            );
            $pinnedCount = $this->feedService->pinnedCount($user, $feedUuid, $manualOnly);
        }

        return new FeedItemsPageResponse(
            items: $this->mapper->toResponses($items),
            pinnedItems: $this->mapper->toResponses($pinnedItems),
            pinnedCount: $pinnedCount,
            nextPage: count($items) === $limit ? $page + 1 : null,
        );
    }
}
