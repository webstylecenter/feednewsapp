<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<User> findAll()
 * @method array<User> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method User|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class UserRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, User::class);
    }
}
