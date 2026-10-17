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
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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
        $itemId = $data->itemId ?? $data->legacyItemId;
        $tagId = $data->tagId ?? $data->legacyTagId;
        if ($itemId === null) {
            throw new UnprocessableEntityHttpException('Feed item is required.');
        }

        $user = $this->currentUser->get();
        $item = $this->itemRepository->findOwnedById($user, $itemId);
        if (!$item instanceof FeedUserItem) {
            throw new NotFoundHttpException('Feed item not found.');
        }

        $tag = $tagId !== null ? $this->tagRepository->findOwnedById($user, $tagId) : null;
        if ($tagId !== null && !$tag instanceof FeedTag) {
            throw new NotFoundHttpException('Tag not found.');
        }

        $item->setTag($tag);
        $this->entityManager->flush();

        return new StatusResponse();
    }
}
