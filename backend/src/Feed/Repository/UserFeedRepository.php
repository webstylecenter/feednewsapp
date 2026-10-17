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
            ->innerJoin('userFeed.feed', 'feed')
            ->andWhere('userFeed.user = :user')
            ->setParameter('user', $user)
            ->orderBy('feed.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $feeds;
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
