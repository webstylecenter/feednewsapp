<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\UserNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNote>
 * @method UserNote|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<UserNote> findAll()
 * @method array<UserNote> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method UserNote|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class UserNoteRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, UserNote::class);
    }
}
