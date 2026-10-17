<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\TagCollectionResponse;
use App\Feed\Dto\Response\TagResponse;
use App\Feed\Repository\FeedTagRepository;
use App\Feed\Repository\UserFeedItemRepository;
use App\Shared\Security\CurrentUser;
use Override;

/** @implements ProviderInterface<TagCollectionResponse> */
final readonly class TagCollectionProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedTagRepository $tagRepository,
        private UserFeedItemRepository $itemRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TagCollectionResponse
    {
        $user = $this->currentUser->get();
        $items = [];
        foreach ($this->tagRepository->findForUser($user) as $tag) {
            $id = $tag->getId();
            if ($id === null) {
                continue;
            }
            $items[] = new TagResponse(
                id: $id,
                uuid: $tag->getUuid()->toRfc4122(),
                name: $tag->getName(),
                color: $tag->getColor(),
                count: $this->itemRepository->countForTag($user, $tag),
            );
        }

        return new TagCollectionResponse($items);
    }
}
