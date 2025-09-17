<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\UserChecklistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserChecklistItem>
 * @method UserChecklistItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<UserChecklistItem> findAll()
 * @method array<UserChecklistItem> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method UserChecklistItem|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class UserChecklistItemRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, UserChecklistItem::class);
    }
}
