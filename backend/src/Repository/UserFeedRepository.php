<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserFeed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFeed>
 * @method UserFeed|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<UserFeed> findAll()
 * @method array<UserFeed> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method UserFeed|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class UserFeedRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, UserFeed::class);
    }
}
