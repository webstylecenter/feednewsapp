<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<Tag> findAll()
 * @method array<Tag> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method Tag|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class TagRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Tag::class);
    }
}
