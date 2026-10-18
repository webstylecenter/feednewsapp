<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\AssignTagRequest;
use App\Feed\Entity\FeedTag;
use App\Feed\Entity\FeedUserItem;
use App\Feed\Repository\FeedTagRepository;
use App\Feed\Repository\UserFeedItemRepository;
use App\Shared\Dto\Response\StatusResponse;
use App\Shared\Security\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<AssignTagRequest, StatusResponse> */
final readonly class AssignTagProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedTagRepository $tagRepository,
        private UserFeedItemRepository $itemRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param AssignTagRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $user = $this->currentUser->get();
        $item = $this->itemRepository->findOwnedByUuid($user, $data->itemUuid);
        if (!$item instanceof FeedUserItem) {
            throw new NotFoundHttpException('Feed item not found.');
        }

        $tag = $data->tagUuid !== null ? $this->tagRepository->findOwnedByUuid($user, $data->tagUuid) : null;
        if ($data->tagUuid !== null && !$tag instanceof FeedTag) {
            throw new NotFoundHttpException('Tag not found.');
        }

        $item->setTag($tag);
        $this->entityManager->flush();

        return new StatusResponse();
    }
}
