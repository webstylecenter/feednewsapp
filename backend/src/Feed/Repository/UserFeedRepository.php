<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;

/** @extends ServiceEntityRepository<FeedUser> */
final class UserFeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedUser::class);
    }

    /** @return list<FeedUser> */
    public function findForUser(User $user): array
    {
        /** @var list<FeedUser> $feeds */
        $feeds = $this->createQueryBuilder('userFeed')
            ->addSelect('feed')
            ->innerJoin('userFeed.feed', 'feed')
            ->andWhere('userFeed.user = :user')
            ->setParameter('user', $user)
            ->orderBy('feed.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $feeds;
    }

    /**
     * @return list<array{source: string, name: string, color: string, icon: string, itemCount: int, url: string}>
     */
    public function findDashboardRowsForUser(User $user): array
    {
        /** @var list<array{
         *     uuid: string,
         *     name: string,
         *     url: string,
         *     subscriptionColor: string|null,
         *     feedColor: string,
         *     icon: string|null,
         *     itemCount: int|string
         * }> $rows
         */
        $rows = $this->createQueryBuilder('userFeed')
            ->select('feed.uuid AS uuid')
            ->addSelect('feed.name AS name')
            ->addSelect('feed.url AS url')
            ->addSelect('userFeed.color AS subscriptionColor')
            ->addSelect('feed.color AS feedColor')
            ->addSelect('userFeed.icon AS icon')
            ->addSelect('COUNT(item.id) AS itemCount')
            ->innerJoin('userFeed.feed', 'feed')
            ->leftJoin('userFeed.items', 'item')
            ->andWhere('userFeed.user = :user')
            ->setParameter('user', $user)
            ->groupBy('userFeed.id', 'feed.id')
            ->orderBy('feed.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'source' => (string) $row['uuid'],
                'name' => $row['name'],
                'color' => $row['subscriptionColor'] ?? $row['feedColor'],
                'icon' => $row['icon'] ?? '',
                'itemCount' => (int) $row['itemCount'],
                'url' => $row['url'],
            ],
            $rows,
        );
    }

    /** @return array<string, int> */
    public function countItemsPerFeedForUser(User $user): array
    {
        /** @var list<array{uuid: string, itemCount: int|string}> $rows */
        $rows = $this->createQueryBuilder('userFeed')
            ->select('feed.uuid AS uuid')
            ->addSelect('COUNT(item.id) AS itemCount')
            ->innerJoin('userFeed.feed', 'feed')
            ->leftJoin('userFeed.items', 'item')
            ->andWhere('userFeed.user = :user')
            ->setParameter('user', $user)
            ->groupBy('feed.id')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['uuid']] = (int) $row['itemCount'];
        }

        return $counts;
    }

    /** @return list<FeedUser> */
    public function findForFeed(Feed $feed): array
    {
        /** @var list<FeedUser> $feeds */
        $feeds = $this->findBy(['feed' => $feed]);
        return $feeds;
    }

    public function findOwnedByFeedUuid(User $user, string $feedUuid): ?FeedUser
    {
        $result = $this->createQueryBuilder('userFeed')
            ->innerJoin('userFeed.feed', 'feed')
            ->andWhere('userFeed.user = :user')
            ->andWhere('feed.uuid = :feedUuid')
            ->setParameter('user', $user)
            ->setParameter('feedUuid', $feedUuid)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result !== null && !$result instanceof FeedUser) {
            throw new LogicException('Expected FeedUser result.');
        }

        return $result;
    }

    public function isFollowing(User $user, Feed $feed): bool
    {
        return $this->count(['user' => $user, 'feed' => $feed]) > 0;
    }
}
