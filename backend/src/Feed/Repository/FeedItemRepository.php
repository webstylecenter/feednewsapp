<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedItem>
 * @method FeedItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<FeedItem> findAll()
 * @method array<FeedItem> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method FeedItem|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class FeedItemRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, FeedItem::class);
    }
}
