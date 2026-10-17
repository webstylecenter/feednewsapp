<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Security\CurrentUser;
use App\User\Dto\Response\NoteCollectionResponse;
use App\User\Mapper\NoteMapper;
use App\User\Repository\UserNoteRepository;
use Override;

/** @implements ProviderInterface<NoteCollectionResponse> */
final readonly class NoteCollectionProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserNoteRepository $repository,
        private NoteMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): NoteCollectionResponse
    {
        $items = array_map(
            $this->mapper->toResponse(...),
            $this->repository->findForUser($this->currentUser->get()),
        );

        return new NoteCollectionResponse($items);
    }
}
