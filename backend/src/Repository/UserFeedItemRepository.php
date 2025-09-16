<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserFeedItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFeedItem>
 * @method UserFeedItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<UserFeedItem> findAll()
 * @method array<UserFeedItem> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method UserFeedItem|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class UserFeedItemRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, UserFeedItem::class);
    }
}
