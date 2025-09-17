<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedCategory>
 * @method FeedCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<FeedCategory> findAll()
 * @method array<FeedCategory> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method FeedCategory|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class FeedCategoryRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, FeedCategory::class);
    }
}
