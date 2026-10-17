<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FeedItem> */
final class FeedItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedItem::class);
    }

    /** @return list<FeedItem> */
    public function findLatestForFeed(Feed $feed, int $limit = 25): array
    {
        /** @var list<FeedItem> $items */
        $items = $this->findBy(['feed' => $feed], ['createdAt' => 'DESC'], $limit);
        return $items;
    }
}
