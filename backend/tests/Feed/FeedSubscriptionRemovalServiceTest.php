<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;
use App\Feed\Service\FeedSubscriptionRemovalService;
use Doctrine\DBAL\Connection;
use LogicException;
use PHPUnit\Framework\TestCase;

final class FeedSubscriptionRemovalServiceTest extends TestCase
{
    public function testRemovingSubscriptionKeepsFeedWhenAnotherSubscriberExists(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::exactly(2))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $parameters): int {
                if ($sql === 'SELECT id FROM feed WHERE id = :feedId FOR UPDATE') {
                    self::assertSame(['feedId' => 20], $parameters);
                    return 20;
                }

                self::assertSame('SELECT 1 FROM feed_user WHERE feed_id = :feedId LIMIT 1', $sql);
                self::assertSame(['feedId' => 20], $parameters);
                return 1;
            });
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with(
                'DELETE FROM feed_user WHERE id = :subscriptionId',
                ['subscriptionId' => 10],
            );
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $service = new FeedSubscriptionRemovalService($connection);

        self::assertFalse($service->remove($this->subscription(10, 20)));
    }

    public function testRemovingLastSubscriptionAlsoRemovesFeed(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::exactly(2))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $parameters): int|false {
                self::assertSame(['feedId' => 20], $parameters);

                return $sql === 'SELECT id FROM feed WHERE id = :feedId FOR UPDATE'
                    ? 20
                    : false;
            });

        $executedStatements = [];
        $connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(
                static function (string $sql, array $parameters) use (&$executedStatements): int {
                    $executedStatements[] = [$sql, $parameters];
                    return 1;
                },
            );
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $service = new FeedSubscriptionRemovalService($connection);

        self::assertTrue($service->remove($this->subscription(10, 20)));
        self::assertSame(
            [
                ['DELETE FROM feed_user WHERE id = :subscriptionId', ['subscriptionId' => 10]],
                ['DELETE FROM feed WHERE id = :feedId', ['feedId' => 20]],
            ],
            $executedStatements,
        );
    }

    public function testRemovingUnpersistedSubscriptionIsRejected(): void
    {
        $feed = $this->createMock(Feed::class);
        $feed->method('getId')->willReturn(20);

        $subscription = $this->createMock(FeedUser::class);
        $subscription->method('getId')->willReturn(null);
        $subscription->method('getFeed')->willReturn($feed);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('beginTransaction');

        $service = new FeedSubscriptionRemovalService($connection);

        $this->expectException(LogicException::class);
        $service->remove($subscription);
    }

    private function subscription(int $subscriptionId, int $feedId): FeedUser
    {
        $feed = $this->createMock(Feed::class);
        $feed->method('getId')->willReturn($feedId);

        $subscription = $this->createMock(FeedUser::class);
        $subscription->method('getId')->willReturn($subscriptionId);
        $subscription->method('getFeed')->willReturn($feed);

        return $subscription;
    }
}
