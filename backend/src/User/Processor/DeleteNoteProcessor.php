<?php

declare(strict_types=1);

namespace App\User\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Api\UriVariable;
use App\Shared\Dto\Response\StatusResponse;
use App\Shared\Security\CurrentUser;
use App\User\Entity\UserNote;
use App\User\Repository\UserNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<mixed, StatusResponse> */
final readonly class DeleteNoteProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserNoteRepository $repository,
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
        $note = $this->repository->findOwnedByUuid(
            $this->currentUser->get(),
            UriVariable::uuid($uriVariables),
        );
        if (!$note instanceof UserNote) {
            throw new NotFoundHttpException('Note not found.');
        }

        $this->entityManager->remove($note);
        $this->entityManager->flush();

        return new StatusResponse();
    }
}
