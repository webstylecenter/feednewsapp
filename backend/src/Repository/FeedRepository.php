<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Feed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feed>
 * @method Feed|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<Feed> findAll()
 * @method array<Feed> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method Feed|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class FeedRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Feed::class);
    }
}
