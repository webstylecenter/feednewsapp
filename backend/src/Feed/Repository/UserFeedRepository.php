<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedUser>
 * @method FeedUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<FeedUser> findAll()
 * @method array<FeedUser> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method FeedUser|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class UserFeedRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, FeedUser::class);
    }
}
