<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedError;
use App\Feed\Enum\FeedErrorType;
use App\User\Entity\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FeedError> */
final class FeedErrorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedError::class);
    }

    public function findMatching(FeedErrorType $type, ?User $user, ?Feed $feed, string $exception): ?FeedError
    {
        return $this->findOneBy([
            'type' => $type,
            'user' => $user,
            'feed' => $feed,
            'exception' => $exception,
        ]);
    }

    public function findLatestForFeed(Feed $feed): ?FeedError
    {
        return $this->findOneBy(['feed' => $feed], ['updatedAt' => 'DESC']);
    }

    /**
     * @param list<Feed> $feeds
     * @return array<int, FeedError>
     */
    public function findLatestForFeeds(array $feeds): array
    {
        if ($feeds === []) {
            return [];
        }

        $oldestUpdatedAt = null;
        foreach ($feeds as $feed) {
            $updatedAt = $feed->getUpdatedAt();
            if (!$oldestUpdatedAt instanceof DateTimeInterface || $updatedAt < $oldestUpdatedAt) {
                $oldestUpdatedAt = $updatedAt;
            }
        }

        $queryBuilder = $this->createQueryBuilder('error')
            ->addSelect('feed')
            ->innerJoin('error.feed', 'feed')
            ->andWhere('error.feed IN (:feeds)')
            ->setParameter('feeds', $feeds)
            ->orderBy('error.updatedAt', 'DESC');

        if ($oldestUpdatedAt instanceof DateTimeInterface) {
            $queryBuilder
                ->andWhere('error.updatedAt > :oldestUpdatedAt')
                ->setParameter('oldestUpdatedAt', $oldestUpdatedAt);
        }

        /** @var list<FeedError> $errors */
        $errors = $queryBuilder->getQuery()->getResult();
        $latestByFeed = [];

        foreach ($errors as $error) {
            $feedId = $error->getFeed()?->getId();
            if ($feedId !== null && !isset($latestByFeed[$feedId])) {
                $latestByFeed[$feedId] = $error;
            }
        }

        return $latestByFeed;
    }

    /** @return list<FeedError> */
    public function findMostFrequent(int $limit = 50): array
    {
        /** @var list<FeedError> $errors */
        $errors = $this->findBy([], ['occurrences' => 'DESC'], $limit);
        return $errors;
    }
}
