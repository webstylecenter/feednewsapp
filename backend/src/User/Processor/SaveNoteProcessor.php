<?php

declare(strict_types=1);

namespace App\User\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Api\UriVariable;
use App\Shared\Security\CurrentUser;
use App\User\Dto\Request\SaveNoteRequest;
use App\User\Dto\Response\NoteMutationResponse;
use App\User\Entity\UserNote;
use App\User\Mapper\NoteMapper;
use App\User\Repository\UserNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<SaveNoteRequest, NoteMutationResponse> */
final readonly class SaveNoteProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserNoteRepository $repository,
        private EntityManagerInterface $entityManager,
        private NoteMapper $mapper,
    ) {
    }

    /**
     * @param SaveNoteRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): NoteMutationResponse
    {
        $user = $this->currentUser->get();
        $content = $data->content !== '' ? $data->content : ($data->note ?? '');
        $uuid = array_key_exists('uuid', $uriVariables) ? UriVariable::uuid($uriVariables) : null;
        $note = $uuid !== null ? $this->repository->findOwnedByUuid($user, $uuid) : null;

        if ($uuid !== null && !$note instanceof UserNote) {
            throw new NotFoundHttpException('Note not found.');
        }

        $name = trim($data->name) !== '' ? mb_substr(trim($data->name), 0, 255) : 'Note';
        if (!$note instanceof UserNote) {
            $note = new UserNote($user, $name, $data->position, $content);
            $this->entityManager->persist($note);
        } else {
            $note
                ->setName($name)
                ->setPosition($data->position)
                ->setContent($content);
        }

        $this->entityManager->flush();

        return new NoteMutationResponse($this->mapper->toResponse($note));
    }
}
