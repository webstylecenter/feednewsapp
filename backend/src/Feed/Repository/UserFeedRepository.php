<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     * @return list<array{id: int, name: string, color: string, icon: string, itemCount: int}>
     */
    public function findDashboardRowsForUser(User $user): array
    {
        /** @var list<array{
         *     id: int|string,
         *     name: string,
         *     subscriptionColor: string|null,
         *     feedColor: string,
         *     icon: string|null,
         *     itemCount: int|string
         * }> $rows
         */
        $rows = $this->createQueryBuilder('userFeed')
            ->select('userFeed.id AS id')
            ->addSelect('feed.name AS name')
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
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'color' => $row['subscriptionColor'] ?? $row['feedColor'],
                'icon' => $row['icon'] ?? '',
                'itemCount' => (int) $row['itemCount'],
            ],
            $rows,
        );
    }

    /** @return list<FeedUser> */
    public function findForFeed(Feed $feed): array
    {
        /** @var list<FeedUser> $feeds */
        $feeds = $this->findBy(['feed' => $feed]);
        return $feeds;
    }

    public function findOwnedById(User $user, int $id): ?FeedUser
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    public function isFollowing(User $user, Feed $feed): bool
    {
        return $this->count(['user' => $user, 'feed' => $feed]) > 0;
    }
}
