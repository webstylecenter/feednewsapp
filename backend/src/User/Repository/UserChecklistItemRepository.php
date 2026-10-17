<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use App\User\Entity\UserChecklistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<UserChecklistItem> */
final class UserChecklistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserChecklistItem::class);
    }

    /** @return list<UserChecklistItem> */
    public function findForUser(User $user): array
    {
        /** @var list<UserChecklistItem> $items */
        $items = $this->createQueryBuilder('item')
            ->andWhere('item.user = :user')
            ->setParameter('user', $user)
            ->orderBy('item.checked', 'ASC')
            ->addOrderBy('item.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $items;
    }

    public function findOwnedById(User $user, int $id): ?UserChecklistItem
    {
        return $this->findOneBy(['user' => $user, 'id' => $id]);
    }
}
