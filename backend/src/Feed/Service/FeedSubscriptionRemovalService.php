<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Entity\FeedUser;
use Doctrine\DBAL\Connection;
use LogicException;
use Throwable;

final readonly class FeedSubscriptionRemovalService
{
    public function __construct(private Connection $connection)
    {
    }

    public function remove(FeedUser $subscription): bool
    {
        $subscriptionId = $subscription->getId();
        $feedId = $subscription->getFeed()->getId();

        if ($subscriptionId === null || $feedId === null) {
            throw new LogicException('Cannot remove an unpersisted feed subscription.');
        }

        $this->connection->beginTransaction();
        try {
            $feedExists = $this->connection->fetchOne(
                'SELECT id FROM feed WHERE id = :feedId FOR UPDATE',
                ['feedId' => $feedId],
            );
            if ($feedExists === false) {
                throw new LogicException('Cannot remove a subscription for a missing feed.');
            }

            $this->connection->executeStatement(
                'DELETE FROM feed_user WHERE id = :subscriptionId',
                ['subscriptionId' => $subscriptionId],
            );

            $remainingSubscription = $this->connection->fetchOne(
                'SELECT 1 FROM feed_user WHERE feed_id = :feedId LIMIT 1',
                ['feedId' => $feedId],
            );
            if ($remainingSubscription !== false) {
                $this->connection->commit();
                return false;
            }

            $this->connection->executeStatement(
                'DELETE FROM feed WHERE id = :feedId',
                ['feedId' => $feedId],
            );
            $this->connection->commit();

            return true;
        } catch (Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }
}
