<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedError;
use App\Feed\Enum\FeedErrorType;
use App\Feed\Repository\FeedErrorRepository;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FeedErrorService
{
    public function __construct(
        private FeedErrorRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function report(FeedErrorType $type, string $exception, ?User $user = null, ?Feed $feed = null): void
    {
        $exception = mb_substr(trim($exception), 0, 10_000);
        $error = $this->repository->findMatching($type, $user, $feed, $exception);
        if ($error instanceof FeedError) {
            $error->setOccurrences($error->getOccurrences() + 1);
        } else {
            $this->entityManager->persist(new FeedError($type, $user, $feed, $exception));
        }

        $this->entityManager->flush();
    }
}
