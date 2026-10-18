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

    /**
     * @param list<string> $guids
     * @return array<string, true>
     */
    public function findExistingGuidMap(Feed $feed, array $guids): array
    {
        if ($guids === []) {
            return [];
        }

        /** @var list<array{guid: string}> $rows */
        $rows = $this->createQueryBuilder('item')
            ->select('item.guid AS guid')
            ->andWhere('item.feed = :feed')
            ->andWhere('item.guid IN (:guids)')
            ->setParameter('feed', $feed)
            ->setParameter('guids', $guids)
            ->getQuery()
            ->getArrayResult();

        $existing = [];
        foreach ($rows as $row) {
            $existing[$row['guid']] = true;
        }

        return $existing;
    }

    /** @return list<FeedItem> */
    public function findLatestForFeed(Feed $feed, int $limit = 25): array
    {
        /** @var list<FeedItem> $items */
        $items = $this->findBy(['feed' => $feed], ['createdAt' => 'DESC'], $limit);
        return $items;
    }
}
