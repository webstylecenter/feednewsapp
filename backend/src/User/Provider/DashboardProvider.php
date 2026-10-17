<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Repository\UserFeedRepository;
use App\Shared\Security\CurrentUser;
use App\User\Dto\Response\DashboardFeedResponse;
use App\User\Dto\Response\DashboardNoteResponse;
use App\User\Dto\Response\DashboardResponse;
use App\User\Dto\Response\DashboardTodoResponse;
use App\User\Entity\UserChecklistItem;
use App\User\Entity\UserNote;
use App\User\Repository\UserChecklistItemRepository;
use App\User\Repository\UserNoteRepository;
use App\Weather\Mapper\WeatherMapper;
use App\Weather\Service\WeatherService;
use Override;

/** @implements ProviderInterface<DashboardResponse> */
final readonly class DashboardProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserNoteRepository $noteRepository,
        private UserChecklistItemRepository $checklistRepository,
        private UserFeedRepository $feedRepository,
        private WeatherService $weatherService,
        private WeatherMapper $weatherMapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DashboardResponse
    {
        $user = $this->currentUser->get();
        $notes = array_map(
            static fn (UserNote $note): DashboardNoteResponse => new DashboardNoteResponse(
                id: $note->getId(),
                name: $note->getName(),
                position: $note->getPosition(),
                content: $note->getContent() ?? '',
            ),
            $this->noteRepository->findForUser($user),
        );
        $todos = array_map(
            static fn (UserChecklistItem $item): DashboardTodoResponse => new DashboardTodoResponse(
                id: $item->getId(),
                item: $item->getItem(),
                checked: $item->getChecked(),
            ),
            $this->checklistRepository->findForUser($user),
        );

        $feeds = array_map(
            static fn (array $row): DashboardFeedResponse => new DashboardFeedResponse(
                id: $row['id'],
                name: $row['name'],
                color: $row['color'],
                icon: $row['icon'],
                itemCount: $row['itemCount'],
            ),
            $this->feedRepository->findDashboardRowsForUser($user),
        );

        $weather = $this->weatherService->current();

        return new DashboardResponse(
            notes: $notes,
            todos: $todos,
            feeds: $feeds,
            weather: $weather !== null ? $this->weatherMapper->toResponse($weather) : null,
        );
    }
}
