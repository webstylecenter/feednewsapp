<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Repository\UserFeedItemRepository;
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
use App\Weather\Service\UserWeatherService;
use Override;

/** @implements ProviderInterface<DashboardResponse> */
final readonly class DashboardProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserNoteRepository $noteRepository,
        private UserChecklistItemRepository $checklistRepository,
        private UserFeedRepository $feedRepository,
        private UserFeedItemRepository $feedItemRepository,
        private UserWeatherService $weatherService,
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
                uuid: $note->getUuid()->toRfc4122(),
                name: $note->getName(),
                position: $note->getPosition(),
                content: $note->getContent() ?? '',
            ),
            $this->noteRepository->findForUser($user),
        );
        $todos = array_map(
            static fn (UserChecklistItem $item): DashboardTodoResponse => new DashboardTodoResponse(
                uuid: $item->getUuid()->toRfc4122(),
                item: $item->getItem(),
                checked: $item->getChecked(),
            ),
            $this->checklistRepository->findForUser($user),
        );

        $feeds = array_map(
            static fn (array $row): DashboardFeedResponse => new DashboardFeedResponse(
                source: $row['source'],
                name: $row['name'],
                color: $row['color'],
                icon: $row['icon'],
                itemCount: $row['itemCount'],
                url: $row['url'],
            ),
            $this->feedRepository->findDashboardRowsForUser($user),
        );

        $manualItemCount = $this->feedItemRepository->countManualForUser($user);
        if ($manualItemCount > 0) {
            $feeds[] = new DashboardFeedResponse(
                source: 'manual',
                name: 'Saved links',
                color: '#337dff',
                icon: '',
                itemCount: $manualItemCount,
                url: null,
            );
        }

        return new DashboardResponse(
            notes: $notes,
            todos: $todos,
            feeds: $feeds,
            weather: $this->weatherMapper->toResponse($this->weatherService->forecast()),
        );
    }
}
