<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Entity\FeedUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final readonly class FeedSubscriptionRemovalService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function remove(FeedUser $subscription): bool
    {
        $feed = $subscription->getFeed();

        $this->entityManager->remove($subscription);
        $this->entityManager->flush();

        $remainingSubscriptions = $this->managerRegistry
            ->getRepository(FeedUser::class)
            ->findBy(['feed' => $feed], null, 1);

        if ($remainingSubscriptions !== []) {
            return false;
        }

        $this->entityManager->remove($feed);
        $this->entityManager->flush();

        return true;
    }
}
