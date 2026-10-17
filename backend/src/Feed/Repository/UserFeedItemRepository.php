<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedTag;
use App\Feed\Entity\FeedUserItem;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FeedUserItem> */
final class UserFeedItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedUserItem::class);
    }

    /** @return list<FeedUserItem> */
    public function findRecentForUser(User $user, int $limit, int $offset): array
    {
        /** @var list<FeedUserItem> $items */
        $items = $this->createQueryBuilder('item')
            ->andWhere('item.user = :user')
            ->setParameter('user', $user)
            ->orderBy('item.pinned', 'DESC')
            ->addOrderBy('item.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    /** @return list<FeedUserItem> */
    public function findOpenedForUser(User $user, int $limit = 50): array
    {
        /** @var list<FeedUserItem> $items */
        $items = $this->createQueryBuilder('item')
            ->andWhere('item.user = :user')
            ->andWhere('item.openedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('item.pinned', 'DESC')
            ->addOrderBy('item.openedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    /** @return list<FeedUserItem> */
    public function searchForUser(User $user, string $query, int $limit = 25): array
    {
        /** @var list<FeedUserItem> $items */
        $items = $this->createQueryBuilder('item')
            ->innerJoin('item.feedItem', 'feedItem')
            ->andWhere('item.user = :user')
            ->andWhere('LOWER(feedItem.title) LIKE :query OR LOWER(feedItem.description) LIKE :query')
            ->setParameter('user', $user)
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
        $items = $this->createQueryBuilder('item')
            ->andWhere('item.user = :user')
            ->andWhere('item.tag = :tag')
            ->setParameter('user', $user)
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
}
