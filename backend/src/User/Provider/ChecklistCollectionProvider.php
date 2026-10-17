<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Security\CurrentUser;
use App\User\Dto\Response\ChecklistCollectionResponse;
use App\User\Mapper\ChecklistMapper;
use App\User\Repository\UserChecklistItemRepository;
use Override;

/** @implements ProviderInterface<ChecklistCollectionResponse> */
final readonly class ChecklistCollectionProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserChecklistItemRepository $repository,
        private ChecklistMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ChecklistCollectionResponse
    {
        $items = array_map(
            $this->mapper->toResponse(...),
            $this->repository->findForUser($this->currentUser->get()),
        );

        return new ChecklistCollectionResponse($items);
    }
}
