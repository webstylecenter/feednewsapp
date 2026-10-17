<?php

declare(strict_types=1);

namespace App\User\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Security\CurrentUser;
use App\User\Dto\Request\CreateChecklistItemRequest;
use App\User\Dto\Response\ChecklistMutationResponse;
use App\User\Entity\UserChecklistItem;
use App\User\Mapper\ChecklistMapper;
use Doctrine\ORM\EntityManagerInterface;
use Override;

/** @implements ProcessorInterface<CreateChecklistItemRequest, ChecklistMutationResponse> */
final readonly class CreateChecklistItemProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private EntityManagerInterface $entityManager,
        private ChecklistMapper $mapper,
    ) {
    }

    /**
     * @param CreateChecklistItemRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ChecklistMutationResponse
    {
        $item = new UserChecklistItem(
            $this->currentUser->get(),
            mb_substr(trim($data->item), 0, 255),
            false,
        );
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return new ChecklistMutationResponse($this->mapper->toResponse($item));
    }
}
