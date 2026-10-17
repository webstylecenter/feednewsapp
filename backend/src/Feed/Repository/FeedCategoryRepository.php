<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FeedCategory> */
final class FeedCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedCategory::class);
    }

    /** @return list<FeedCategory> */
    public function findAllOrdered(): array
    {
        /** @var list<FeedCategory> $categories */
        $categories = $this->findBy([], ['name' => 'ASC']);
        return $categories;
    }
}
