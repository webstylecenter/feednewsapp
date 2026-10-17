<?php

declare(strict_types=1);

namespace App\Feed\Repository;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedError;
use App\Feed\Enum\FeedErrorType;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FeedError> */
final class FeedErrorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedError::class);
    }

    public function findMatching(FeedErrorType $type, ?User $user, ?Feed $feed, string $exception): ?FeedError
    {
        return $this->findOneBy([
            'type' => $type,
            'user' => $user,
            'feed' => $feed,
            'exception' => $exception,
        ]);
    }

    /** @return list<FeedError> */
    public function findMostFrequent(int $limit = 50): array
    {
        /** @var list<FeedError> $errors */
        $errors = $this->findBy([], ['occurrences' => 'DESC'], $limit);
        return $errors;
    }
}
