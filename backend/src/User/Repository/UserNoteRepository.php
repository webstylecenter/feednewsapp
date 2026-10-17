<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use App\User\Entity\UserNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<UserNote> */
final class UserNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNote::class);
    }

    /** @return list<UserNote> */
    public function findForUser(User $user): array
    {
        /** @var list<UserNote> $notes */
        $notes = $this->findBy(['user' => $user], ['position' => 'ASC', 'id' => 'ASC']);
        return $notes;
    }

    public function findOwnedById(User $user, int $id): ?UserNote
    {
        return $this->findOneBy(['user' => $user, 'id' => $id]);
    }
}
