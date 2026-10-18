<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedTag;
use App\Feed\Entity\FeedUserItem;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

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
            ->leftJoin('item.userFeed', 'userFeed')
            ->leftJoin('userFeed.feed', 'subscriptionFeed')
            ->andWhere('item.user = :user')
            ->andWhere('item.pinned = true')
            ->setParameter('user', $user);

        $this->applySourceFilter($queryBuilder, $feedUuid, $manualOnly);

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
