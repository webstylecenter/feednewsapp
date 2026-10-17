<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Request\RequestData;
use App\Shared\Security\CurrentUser;
use App\User\Entity\UserNote;
use App\User\Repository\UserNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use LogicException;

#[Route('/api/notes')]
final class NotesController extends AbstractController
{
    #[Route('', name: 'api_notes_index', methods: ['GET'])]
    public function index(CurrentUser $currentUser, UserNoteRepository $repository): JsonResponse
    {
        return $this->json([
            'items' => array_map(
                static fn (UserNote $note): array => self::present($note),
                $repository->findForUser($currentUser->get()),
            ),
        ]);
    }

    #[Route('', name: 'api_notes_save', methods: ['POST'])]
    public function save(
        Request $request,
        CurrentUser $currentUser,
        UserNoteRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = RequestData::all($request);
        $content = RequestData::string($data, 'content', RequestData::string($data, 'note'));
        $user = $currentUser->get();
        $id = RequestData::int($data, 'id');
        $note = $id !== null ? $repository->findOwnedById($user, $id) : null;
        if ($id !== null && !$note instanceof UserNote) {
            throw $this->createAccessDeniedException();
        }

        $name = RequestData::string($data, 'name', 'Note');
        $position = RequestData::int($data, 'position') ?? 0;
        if (!$note instanceof UserNote) {
            $note = new UserNote($user, $name !== '' ? $name : 'Note', $position, $content);
            $entityManager->persist($note);
        } else {
            $note
                ->setName($name !== '' ? $name : 'Note')
                ->setPosition($position)
                ->setContent($content);
        }

        $entityManager->flush();
        return $this->json(['status' => 'success', 'item' => self::present($note)]);
    }

    #[Route('/{id<\d+>}', name: 'api_notes_remove', methods: ['DELETE'])]
    public function remove(
        int $id,
        CurrentUser $currentUser,
        UserNoteRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $note = $repository->findOwnedById($currentUser->get(), $id);
        if (!$note instanceof UserNote) {
            throw $this->createNotFoundException('Note not found.');
        }

        $entityManager->remove($note);
        $entityManager->flush();
        return $this->json(['status' => 'success']);
    }

    /** @return array{id: int, uuid: string, name: string, content: string, position: int} */
    private static function present(UserNote $note): array
    {
        $id = $note->getId();
        if ($id === null) {
            throw new LogicException('Cannot present an unpersisted note.');
        }

        return [
            'id' => $id,
            'uuid' => $note->getUuid()->toRfc4122(),
            'name' => $note->getName(),
            'content' => $note->getContent() ?? '',
            'position' => $note->getPosition(),
        ];
    }
}
