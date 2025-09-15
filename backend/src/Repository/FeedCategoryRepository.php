<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Feed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedCategoryRepository>
 * @method FeedCategoryRepository|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<FeedCategoryRepository> findAll()
 * @method array<FeedCategoryRepository> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method FeedCategoryRepository|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class FeedCategoryRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Feed::class);
    }
}
