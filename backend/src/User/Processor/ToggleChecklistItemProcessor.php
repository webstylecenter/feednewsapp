<?php

declare(strict_types=1);

namespace App\User\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Api\UriVariable;
use App\Shared\Security\CurrentUser;
use App\User\Dto\Response\ChecklistMutationResponse;
use App\User\Entity\UserChecklistItem;
use App\User\Mapper\ChecklistMapper;
use App\User\Repository\UserChecklistItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<mixed, ChecklistMutationResponse> */
final readonly class ToggleChecklistItemProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserChecklistItemRepository $repository,
        private EntityManagerInterface $entityManager,
        private ChecklistMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ChecklistMutationResponse
    {
        $item = $this->repository->findOwnedByUuid(
            $this->currentUser->get(),
            UriVariable::uuid($uriVariables),
        );
        if (!$item instanceof UserChecklistItem) {
            throw new NotFoundHttpException('Todo not found.');
        }

        $item->setChecked(!$item->getChecked());
        $this->entityManager->flush();

        return new ChecklistMutationResponse($this->mapper->toResponse($item));
    }
}
