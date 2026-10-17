<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\FeedTag;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FeedTag> */
final class FeedTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedTag::class);
    }

    /** @return list<FeedTag> */
    public function findForUser(User $user): array
    {
        /** @var list<FeedTag> $tags */
        $tags = $this->findBy(['user' => $user], ['name' => 'ASC']);
        return $tags;
    }

    public function findOwnedById(User $user, int $id): ?FeedTag
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }
}
