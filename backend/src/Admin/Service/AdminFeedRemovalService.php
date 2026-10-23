<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Feed\Entity\Feed;
use Doctrine\DBAL\Connection;
use LogicException;
use Throwable;

final readonly class AdminFeedRemovalService
{
    public function __construct(private Connection $connection)
    {
    }

    public function remove(Feed $feed): void
    {
        $feedId = $feed->getId();
        if ($feedId === null) {
            throw new LogicException('Cannot remove an unpersisted feed.');
        }

        $this->connection->beginTransaction();
        try {
            $feedExists = $this->connection->fetchOne(
                'SELECT id FROM feed WHERE id = :feedId FOR UPDATE',
                ['feedId' => $feedId],
            );
            if ($feedExists === false) {
                throw new LogicException('Cannot remove a missing feed.');
            }

            $this->connection->executeStatement(
                'DELETE FROM feed WHERE id = :feedId',
                ['feedId' => $feedId],
            );
            $this->connection->commit();
        } catch (Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }
}
