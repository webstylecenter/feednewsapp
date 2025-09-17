<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedUserItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedUserItem>
 * @method FeedUserItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<FeedUserItem> findAll()
 * @method array<FeedUserItem> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method FeedUserItem|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class UserFeedItemRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, FeedUserItem::class);
    }
}
