<?php

declare(strict_types=1);

namespace App\Admin\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Admin\Dto\Response\AdminDashboardResponse;
use App\Admin\Dto\Response\AdminFeedErrorResponse;
use App\Admin\Dto\Response\AdminFeedResponse;
use App\Admin\Dto\Response\AdminUserResponse;
use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedError;
use App\Feed\Repository\FeedErrorRepository;
use App\Feed\Repository\FeedRepository;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use DateTimeInterface;
use LogicException;
use Override;

/** @implements ProviderInterface<AdminDashboardResponse> */
final readonly class AdminDashboardProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private FeedRepository $feedRepository,
        private FeedErrorRepository $errorRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminDashboardResponse
    {
        $users = array_map(
            static fn (User $user): AdminUserResponse => new AdminUserResponse(
                id: $user->getId(),
                uuid: $user->getUuid()->toRfc4122(),
                name: $user->getName(),
                email: $user->getEmail(),
                enabled: $user->isEnabled(),
                isAdmin: $user->isAdmin(),
                lastLogin: $user->getLastLogin()?->format(DATE_ATOM),
                createdAt: self::formatTimestamp($user->getCreatedAt()),
            ),
            $this->userRepository->findBy([], ['createdAt' => 'DESC']),
        );
        $users = array_values($users);

        $feeds = array_map(
            static fn (Feed $feed): AdminFeedResponse => new AdminFeedResponse(
                id: $feed->getId(),
                name: $feed->getName(),
                url: $feed->getUrl(),
                createdAt: self::formatTimestamp($feed->getCreatedAt()),
                updatedAt: self::formatTimestamp($feed->getUpdatedAt()),
            ),
            array_slice($this->feedRepository->findBy([], ['createdAt' => 'DESC']), 0, 20),
        );

        $errors = array_map(
            static fn (FeedError $error): AdminFeedErrorResponse => new AdminFeedErrorResponse(
                id: $error->getId(),
                type: $error->getType()->value,
                occurrences: $error->getOccurrences(),
                message: $error->getException(),
                user: $error->getUser()?->getEmail(),
                feed: $error->getFeed()?->getName(),
                updatedAt: self::formatTimestamp($error->getUpdatedAt()),
            ),
            $this->errorRepository->findMostFrequent(),
        );

        return new AdminDashboardResponse($users, $feeds, $errors);
    }

    private static function formatTimestamp(?DateTimeInterface $dateTime): string
    {
        if ($dateTime === null) {
            throw new LogicException('Persisted entity is missing a timestamp.');
        }

        return $dateTime->format(DATE_ATOM);
    }
}
