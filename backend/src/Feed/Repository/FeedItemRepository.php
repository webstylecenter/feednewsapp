<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedItem;
use DateTimeImmutable;
use DateTimeInterface;
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

    /**
     * @param list<Feed> $feeds
     * @return array<int, string>
     */
    public function findLatestCreatedAtByFeeds(array $feeds): array
    {
        if ($feeds === []) {
            return [];
        }

        /** @var list<array{feedId: int|string, lastItemAt: DateTimeInterface|string|null}> $rows */
        $rows = $this->createQueryBuilder('item')
            ->select('IDENTITY(item.feed) AS feedId')
            ->addSelect('MAX(item.createdAt) AS lastItemAt')
            ->andWhere('item.feed IN (:feeds)')
            ->setParameter('feeds', $feeds)
            ->groupBy('item.feed')
            ->getQuery()
            ->getArrayResult();

        $latestByFeed = [];
        foreach ($rows as $row) {
            if ($row['lastItemAt'] === null) {
                continue;
            }

            $lastItemAt = $row['lastItemAt'] instanceof DateTimeInterface
                ? $row['lastItemAt']
                : new DateTimeImmutable($row['lastItemAt']);
            $latestByFeed[(int) $row['feedId']] = $lastItemAt->format(DATE_ATOM);
        }

        return $latestByFeed;
    }
}
