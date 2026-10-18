<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\FeedItemsResponse;
use App\Feed\Entity\FeedTag;
use App\Feed\Mapper\FeedItemMapper;
use App\Feed\Repository\FeedTagRepository;
use App\Feed\Repository\UserFeedItemRepository;
use App\Shared\Api\UriVariable;
use App\Shared\Security\CurrentUser;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<FeedItemsResponse> */
final readonly class FeedTagItemsProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedTagRepository $tagRepository,
        private UserFeedItemRepository $itemRepository,
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
        $user = $this->currentUser->get();
        $tag = $this->tagRepository->findOwnedByUuid($user, UriVariable::uuid($uriVariables));
        if (!$tag instanceof FeedTag) {
            throw new NotFoundHttpException('Tag not found.');
        }

        return new FeedItemsResponse(
            $this->mapper->toResponses($this->itemRepository->findByTagForUser($user, $tag)),
        );
    }
}
