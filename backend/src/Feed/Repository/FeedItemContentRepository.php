<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedItemContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedItemContent>
 * @method FeedItemContent|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<FeedItemContent> findAll()
 * @method array<FeedItemContent> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method FeedItemContent|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class FeedItemContentRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, FeedItemContent::class);
    }
}
