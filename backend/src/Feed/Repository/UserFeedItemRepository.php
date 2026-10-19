<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedTag;
use App\Feed\Entity\FeedUserItem;
use App\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;

/** @extends ServiceEntityRepository<FeedUserItem> */
final class UserFeedItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedUserItem::class);
    }

    /** @return list<FeedUserItem> */
    public function findRecentForUser(
        User $user,
        int $limit,
        int $offset,
        ?string $feedUuid = null,
        bool $manualOnly = false,
    ): array {
        $queryBuilder = $this->baseReaderQuery($user);
        $this->applySourceFilter($queryBuilder, $feedUuid, $manualOnly);

        /** @var list<FeedUserItem> $items */
        $items = $queryBuilder
            ->andWhere('item.pinned = false')
            ->orderBy('item.createdAt', 'DESC')
            ->addOrderBy('item.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    /** @return list<FeedUserItem> */
    public function findPinnedForUser(
        User $user,
        ?int $limit = null,
        ?string $feedUuid = null,
        bool $manualOnly = false,
    ): array {
        $queryBuilder = $this->baseReaderQuery($user);
        $this->applySourceFilter($queryBuilder, $feedUuid, $manualOnly);
        $queryBuilder
            ->andWhere('item.pinned = true')
            ->orderBy('item.createdAt', 'DESC')
            ->addOrderBy('item.id', 'DESC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        /** @var list<FeedUserItem> $items */
        $items = $queryBuilder->getQuery()->getResult();

        return $items;
    }

    public function countPinnedForUser(
        User $user,
        ?string $feedUuid = null,
        bool $manualOnly = false,
    ): int {
        $queryBuilder = $this->createQueryBuilder('item')
            ->select('COUNT(item.id)')
            ->andWhere('item.user = :user')
            ->andWhere('item.pinned = true')
            ->setParameter('user', $user);

        if ($manualOnly) {
            $queryBuilder->andWhere('item.userFeed IS NULL');
        } elseif ($feedUuid !== null) {
            $queryBuilder
                ->innerJoin('item.userFeed', 'userFeed')
                ->innerJoin('userFeed.feed', 'subscriptionFeed')
                ->andWhere('subscriptionFeed.uuid = :feedUuid')
                ->setParameter('feedUuid', $feedUuid);
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countManualForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('item')
            ->select('COUNT(item.id)')
            ->andWhere('item.user = :user')
            ->andWhere('item.userFeed IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<int, int> */
    public function countForTags(User $user): array
    {
        /** @var list<array{tagId: int|string, itemCount: int|string}> $rows */
        $rows = $this->createQueryBuilder('item')
            ->select('IDENTITY(item.tag) AS tagId')
            ->addSelect('COUNT(item.id) AS itemCount')
            ->andWhere('item.user = :user')
            ->andWhere('item.tag IS NOT NULL')
            ->setParameter('user', $user)
            ->groupBy('item.tag')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['tagId']] = (int) $row['itemCount'];
        }

        return $counts;
    }

    /** @return list<FeedUserItem> */
    public function findOpenedForUser(User $user, int $limit = 50): array
    {
        /** @var list<FeedUserItem> $items */
        $items = $this->baseReaderQuery($user)
            ->andWhere('item.openedAt IS NOT NULL')
            ->orderBy('item.openedAt', 'DESC')
            ->addOrderBy('item.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    /** @return list<FeedUserItem> */
    public function searchForUser(User $user, string $query, int $limit = 25): array
    {
        /** @var list<FeedUserItem> $items */
        $items = $this->baseReaderQuery($user)
            ->andWhere('LOWER(feedItem.title) LIKE :query OR LOWER(feedItem.description) LIKE :query')
            ->setParameter('query', '%' . mb_strtolower($query) . '%')
            ->orderBy('item.pinned', 'DESC')
            ->addOrderBy('item.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    /** @return list<FeedUserItem> */
    public function findByTagForUser(User $user, FeedTag $tag): array
    {
        /** @var list<FeedUserItem> $items */
        $items = $this->baseReaderQuery($user)
            ->andWhere('item.tag = :tag')
            ->setParameter('tag', $tag)
            ->orderBy('item.pinned', 'DESC')
            ->addOrderBy('item.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $items;
    }

    public function countForTag(User $user, FeedTag $tag): int
    {
        return (int) $this->createQueryBuilder('item')
            ->select('COUNT(item.id)')
            ->andWhere('item.user = :user')
            ->andWhere('item.tag = :tag')
            ->setParameter('user', $user)
            ->setParameter('tag', $tag)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOwnedById(User $user, int $id): ?FeedUserItem
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    public function findOwnedByUuid(User $user, string $uuid): ?FeedUserItem
    {
        return $this->findOneBy(['uuid' => $uuid, 'user' => $user]);
    }

    /** @param list<int> $ids */
    public function markViewedByIdsForUser(User $user, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE feed_user_item '
            . 'SET viewed = 1, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE user_id = :userId AND viewed = 0 AND id IN (:ids)',
            [
                'userId' => $this->persistedUserId($user),
                'ids' => $ids,
            ],
            [
                'ids' => ArrayParameterType::INTEGER,
            ],
        );
    }

    /** @return array{items: int, unread: int, opened: int, pinned: int} */
    public function dashboardTotalsForUser(User $user): array
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT '
            . '(SELECT COUNT(*) FROM feed_user_item WHERE user_id = :userId AND opened_at IS NULL) AS unread_count, '
            . '(SELECT COUNT(*) FROM feed_user_item WHERE user_id = :userId AND opened_at IS NOT NULL) AS opened_count, '
            . '(SELECT COUNT(*) FROM feed_user_item WHERE user_id = :userId AND pinned = 1) AS pinned_count',
            ['userId' => $this->persistedUserId($user)],
        );

        if ($row === false) {
            return ['items' => 0, 'unread' => 0, 'opened' => 0, 'pinned' => 0];
        }

        $unread = $this->countValue($row['unread_count'] ?? null);
        $opened = $this->countValue($row['opened_count'] ?? null);

        return [
            'items' => $unread + $opened,
            'unread' => $unread,
            'opened' => $opened,
            'pinned' => $this->countValue($row['pinned_count'] ?? null),
        ];
    }

    /** @return list<array{name: string, count: int}> */
    public function countByCategoryForUser(User $user): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT CASE "
            . "WHEN grouped.user_feed_id IS NULL THEN 'Saved links' "
            . "WHEN category.id IS NULL THEN 'Uncategorised' "
            . "ELSE category.name END AS category_name, "
            . 'SUM(grouped.item_count) AS item_count '
            . 'FROM ('
            . 'SELECT user_feed_id, COUNT(*) AS item_count '
            . 'FROM feed_user_item '
            . 'WHERE user_id = :userId '
            . 'GROUP BY user_feed_id'
            . ') grouped '
            . 'LEFT JOIN feed_user user_feed ON user_feed.id = grouped.user_feed_id '
            . 'LEFT JOIN feed feed ON feed.id = user_feed.feed_id '
            . 'LEFT JOIN feed_category category ON category.id = feed.category_id '
            . 'GROUP BY category_name '
            . 'ORDER BY item_count DESC, category_name ASC',
            ['userId' => $this->persistedUserId($user)],
        );

        $statistics = [];
        foreach ($rows as $row) {
            $name = $row['category_name'] ?? null;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $statistics[] = [
                'name' => $name,
                'count' => $this->countValue($row['item_count'] ?? null),
            ];
        }

        return $statistics;
    }

    /** @return array<string, int> */
    public function countCreatedByDayForUser(User $user, DateTimeImmutable $since): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT DATE(created_at) AS date_key, COUNT(*) AS item_count '
            . 'FROM feed_user_item '
            . 'WHERE user_id = :userId AND created_at >= :since '
            . 'GROUP BY DATE(created_at) '
            . 'ORDER BY date_key ASC',
            [
                'userId' => $this->persistedUserId($user),
                'since' => $since->format('Y-m-d 00:00:00'),
            ],
        );

        $counts = [];
        foreach ($rows as $row) {
            $date = $row['date_key'] ?? null;
            if (!is_string($date)) {
                continue;
            }

            $counts[$date] = $this->countValue($row['item_count'] ?? null);
        }

        return $counts;
    }

    /** @return list<array{name: string, count: int}> */
    public function topFeedsForUser(User $user, DateTimeImmutable $since, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT feed.name AS feed_name, grouped.item_count AS item_count '
            . 'FROM ('
            . 'SELECT user_feed_id, COUNT(*) AS item_count '
            . 'FROM feed_user_item '
            . 'WHERE user_id = :userId AND created_at >= :since AND user_feed_id IS NOT NULL '
            . 'GROUP BY user_feed_id'
            . ') grouped '
            . 'INNER JOIN feed_user user_feed ON user_feed.id = grouped.user_feed_id '
            . 'INNER JOIN feed feed ON feed.id = user_feed.feed_id '
            . 'ORDER BY grouped.item_count DESC, feed.name ASC '
            . 'LIMIT ' . $limit,
            [
                'userId' => $this->persistedUserId($user),
                'since' => $since->format('Y-m-d 00:00:00'),
            ],
        );

        $statistics = [];
        foreach ($rows as $row) {
            $name = $row['feed_name'] ?? null;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $statistics[] = [
                'name' => $name,
                'count' => $this->countValue($row['item_count'] ?? null),
            ];
        }

        return $statistics;
    }

    private function persistedUserId(User $user): int
    {
        $id = $user->getId();
        if ($id === null) {
            throw new LogicException('Dashboard statistics require a persisted user.');
        }

        return $id;
    }

    private function countValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return 0;
    }

    private function applySourceFilter(
        QueryBuilder $queryBuilder,
        ?string $feedUuid,
        bool $manualOnly,
    ): void {
        if ($manualOnly) {
            $queryBuilder->andWhere('item.userFeed IS NULL');
            return;
        }

        if ($feedUuid !== null) {
            $queryBuilder
                ->andWhere('subscriptionFeed.uuid = :feedUuid')
                ->setParameter('feedUuid', $feedUuid);
        }
    }

    private function baseReaderQuery(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('item')
            ->addSelect('feedItem', 'userFeed', 'subscriptionFeed', 'itemFeed', 'tag')
            ->innerJoin('item.feedItem', 'feedItem')
            ->leftJoin('item.userFeed', 'userFeed')
            ->leftJoin('userFeed.feed', 'subscriptionFeed')
            ->leftJoin('feedItem.feed', 'itemFeed')
            ->leftJoin('item.tag', 'tag')
            ->andWhere('item.user = :user')
            ->setParameter('user', $user);
    }
}
