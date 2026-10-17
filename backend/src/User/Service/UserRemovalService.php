<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use Doctrine\DBAL\Connection;
use LogicException;
use Throwable;

final readonly class UserRemovalService
{
    public function __construct(private Connection $connection)
    {
    }

    public function remove(User $user): void
    {
        $userId = $user->getId();
        if ($userId === null) {
            throw new LogicException('Cannot remove an unpersisted user.');
        }

        $this->connection->beginTransaction();
        try {
            $parameters = ['userId' => $userId];
            $this->connection->executeStatement(
                'UPDATE feed_error SET user_id = NULL WHERE user_id = :userId',
                $parameters,
            );
            $this->connection->executeStatement(
                'DELETE FROM feed_user_item WHERE user_id = :userId',
                $parameters,
            );
            $this->connection->executeStatement(
                'DELETE FROM feed_tag WHERE user_id = :userId',
                $parameters,
            );
            $this->connection->executeStatement(
                'DELETE FROM feed_user WHERE user_id = :userId',
                $parameters,
            );
            $this->connection->executeStatement(
                'DELETE FROM user_note WHERE user_id = :userId',
                $parameters,
            );
            $this->connection->executeStatement(
                'DELETE FROM user_checklist_item WHERE user_id = :userId',
                $parameters,
            );
            $this->connection->executeStatement(
                'DELETE FROM user_setting WHERE user_id = :userId',
                $parameters,
            );
            $this->connection->executeStatement(
                'DELETE FROM user WHERE id = :userId',
                $parameters,
            );
            $this->connection->commit();
        } catch (Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }
}
