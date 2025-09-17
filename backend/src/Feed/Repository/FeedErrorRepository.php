<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedError;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedError>
 * @method FeedError|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<FeedError> findAll()
 * @method array<FeedError> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method FeedError|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class FeedErrorRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, FeedError::class);
    }
}
