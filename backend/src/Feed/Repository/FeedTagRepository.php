<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedTag>
 * @method FeedTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<FeedTag> findAll()
 * @method array<FeedTag> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method FeedTag|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class FeedTagRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, FeedTag::class);
    }
}
