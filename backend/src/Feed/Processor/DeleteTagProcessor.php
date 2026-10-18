<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Entity\FeedTag;
use App\Feed\Repository\FeedTagRepository;
use App\Feed\Repository\UserFeedItemRepository;
use App\Shared\Api\UriVariable;
use App\Shared\Dto\Response\StatusResponse;
use App\Shared\Security\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<mixed, StatusResponse> */
final readonly class DeleteTagProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedTagRepository $tagRepository,
        private UserFeedItemRepository $itemRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $user = $this->currentUser->get();
        $tag = $this->tagRepository->findOwnedByUuid($user, UriVariable::uuid($uriVariables));
        if (!$tag instanceof FeedTag) {
            throw new NotFoundHttpException('Tag not found.');
        }

        foreach ($this->itemRepository->findByTagForUser($user, $tag) as $item) {
            $item->setTag(null);
        }
        $this->entityManager->remove($tag);
        $this->entityManager->flush();

        return new StatusResponse();
    }
}
