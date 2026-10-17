<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Request\RequestData;
use App\Shared\Security\CurrentUser;
use App\User\Entity\UserChecklistItem;
use App\User\Repository\UserChecklistItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use LogicException;

#[Route('/api/checklist')]
final class ChecklistController extends AbstractController
{
    #[Route('', name: 'api_checklist_index', methods: ['GET'])]
    public function index(CurrentUser $currentUser, UserChecklistItemRepository $repository): JsonResponse
    {
        return $this->json([
            'items' => array_map(
                static fn (UserChecklistItem $item): array => self::present($item),
                $repository->findForUser($currentUser->get()),
            ),
        ]);
    }

    #[Route('', name: 'api_checklist_add', methods: ['POST'])]
    public function add(
        Request $request,
        CurrentUser $currentUser,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $itemText = RequestData::string(RequestData::all($request), 'item');
        if (mb_strlen($itemText) < 3) {
            return $this->json(['status' => 'error', 'message' => 'Todo must contain at least 3 characters.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $item = new UserChecklistItem($currentUser->get(), mb_substr($itemText, 0, 255), false);
        $entityManager->persist($item);
        $entityManager->flush();

        return $this->json(['status' => 'success', 'item' => self::present($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}', name: 'api_checklist_toggle', methods: ['PATCH'])]
    public function toggle(
        int $id,
        CurrentUser $currentUser,
        UserChecklistItemRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $item = $repository->findOwnedById($currentUser->get(), $id);
        if (!$item instanceof UserChecklistItem) {
            throw $this->createNotFoundException('Todo not found.');
        }

        $item->setChecked(!$item->getChecked());
        $entityManager->flush();
        return $this->json(['status' => 'success', 'item' => self::present($item)]);
    }

    /** @return array{id: int, uuid: string, item: string, checked: bool} */
    private static function present(UserChecklistItem $item): array
    {
        $id = $item->getId();
        if ($id === null) {
            throw new LogicException('Cannot present an unpersisted checklist item.');
        }

        return [
            'id' => $id,
            'uuid' => $item->getUuid()->toRfc4122(),
            'item' => $item->getItem(),
            'checked' => $item->getChecked(),
        ];
    }
}
