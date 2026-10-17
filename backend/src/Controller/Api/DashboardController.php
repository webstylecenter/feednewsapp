<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Feed\Repository\UserFeedRepository;
use App\Shared\Security\CurrentUser;
use App\User\Entity\UserChecklistItem;
use App\User\Entity\UserNote;
use App\User\Repository\UserChecklistItemRepository;
use App\User\Repository\UserNoteRepository;
use App\Weather\Service\WeatherPresenter;
use App\Weather\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function index(
        CurrentUser $currentUser,
        UserNoteRepository $noteRepository,
        UserChecklistItemRepository $checklistRepository,
        UserFeedRepository $feedRepository,
        WeatherService $weatherService,
        WeatherPresenter $weatherPresenter,
    ): JsonResponse {
        $user = $currentUser->get();
        $notes = array_map(
            static fn (UserNote $note): array => [
                'id' => $note->getId(),
                'name' => $note->getName(),
                'position' => $note->getPosition(),
                'content' => $note->getContent() ?? '',
            ],
            $noteRepository->findForUser($user),
        );
        $todos = array_map(
            static fn (UserChecklistItem $item): array => [
                'id' => $item->getId(),
                'item' => $item->getItem(),
                'checked' => $item->getChecked(),
            ],
            $checklistRepository->findForUser($user),
        );

        $feeds = [];
        foreach ($feedRepository->findForUser($user) as $subscription) {
            $feeds[] = [
                'id' => $subscription->getId(),
                'name' => $subscription->getFeed()->getName(),
                'color' => $subscription->getColor() ?? $subscription->getFeed()->getColor(),
                'icon' => $subscription->getIcon() ?? '',
                'itemCount' => $subscription->getItems()->count(),
            ];
        }

        $weather = $weatherService->current();

        return $this->json([
            'notes' => $notes,
            'todos' => $todos,
            'feeds' => $feeds,
            'weather' => $weather !== null ? $weatherPresenter->present($weather) : null,
        ]);
    }
}
