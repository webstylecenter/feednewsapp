<?php

declare(strict_types=1);

namespace App\Tests\Admin;

use App\Admin\Service\AdminFeedRemovalService;
use App\Feed\Entity\Feed;
use Doctrine\DBAL\Connection;
use LogicException;
use PHPUnit\Framework\TestCase;

final class AdminFeedRemovalServiceTest extends TestCase
{
    public function testFeedIsRemovedWithOneDatabaseDelete(): void
    {
        $feed = $this->createStub(Feed::class);
        $feed->method('getId')->willReturn(20);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT id FROM feed WHERE id = :feedId FOR UPDATE',
                ['feedId' => 20],
            )
            ->willReturn(20);
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with(
                'DELETE FROM feed WHERE id = :feedId',
                ['feedId' => 20],
            );
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        (new AdminFeedRemovalService($connection))->remove($feed);
    }

    public function testUnpersistedFeedIsRejectedBeforeStartingTransaction(): void
    {
        $feed = $this->createStub(Feed::class);
        $feed->method('getId')->willReturn(null);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('beginTransaction');

        $service = new AdminFeedRemovalService($connection);

        $this->expectException(LogicException::class);
        $service->remove($feed);
    }
}
