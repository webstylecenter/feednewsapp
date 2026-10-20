<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\Feed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Feed> */
final class FeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feed::class);
    }

    /** @return list<Feed> */
    public function findAllOrdered(): array
    {
        /** @var list<Feed> $feeds */
        $feeds = $this->createQueryBuilder('feed')
            ->addSelect('category')
            ->leftJoin('feed.category', 'category')
            ->orderBy('category.name', 'ASC')
            ->addOrderBy('feed.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $feeds;
    }

    /** @return list<Feed> */
    public function findOldestUpdatedFirst(): array
    {
        /** @var list<Feed> $feeds */
        $feeds = $this->findBy([], ['updatedAt' => 'ASC']);
        return $feeds;
    }
}
