<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;
use App\Feed\Service\FeedSubscriptionRemovalService;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

final class FeedSubscriptionRemovalServiceTest extends TestCase
{
    public function testRemovingSubscriptionKeepsFeedWhenAnotherSubscriberExists(): void
    {
        $feed = new Feed(null, 'Feed', '#ffffff', 'https://example.com/feed');
        $subscription = new FeedUser($this->user('first@example.com'), $feed);
        $otherSubscription = new FeedUser($this->user('second@example.com'), $feed);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('remove')
            ->with($subscription);
        $entityManager->expects(self::once())->method('flush');

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['feed' => $feed], null, 1)
            ->willReturn([$otherSubscription]);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())
            ->method('getRepository')
            ->with(FeedUser::class)
            ->willReturn($repository);

        $service = new FeedSubscriptionRemovalService($entityManager, $managerRegistry);

        self::assertFalse($service->remove($subscription));
    }

    public function testRemovingLastSubscriptionAlsoRemovesFeed(): void
    {
        $feed = new Feed(null, 'Feed', '#ffffff', 'https://example.com/feed');
        $subscription = new FeedUser($this->user('only@example.com'), $feed);
        $removedEntities = [];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))
            ->method('remove')
            ->willReturnCallback(static function (object $entity) use (&$removedEntities): void {
                $removedEntities[] = $entity;
            });
        $entityManager->expects(self::exactly(2))->method('flush');

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['feed' => $feed], null, 1)
            ->willReturn([]);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())
            ->method('getRepository')
            ->with(FeedUser::class)
            ->willReturn($repository);

        $service = new FeedSubscriptionRemovalService($entityManager, $managerRegistry);

        self::assertTrue($service->remove($subscription));
        self::assertSame([$subscription, $feed], $removedEntities);
    }

    private function user(string $email): User
    {
        return new User(
            email: $email,
            name: 'Test User',
            password: 'hashed',
            hideXFrameNotice: false,
            enabled: true,
        );
    }
}
