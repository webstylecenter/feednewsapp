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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProviderInterface<FeedItemsResponse> */
final readonly class FeedSearchProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
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
        $query = trim((string) $this->requestStack->getCurrentRequest()?->query->get('q', ''));
        if (mb_strlen($query) < 2) {
            throw new UnprocessableEntityHttpException('Search query must contain at least 2 characters.');
        }

        return new FeedItemsResponse(
            $this->mapper->toResponses($this->repository->searchForUser($this->currentUser->get(), $query)),
        );
    }
}
