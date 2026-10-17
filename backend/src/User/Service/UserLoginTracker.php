<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UserLoginTracker
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function track(User $user, Request $request): void
    {
        $user
            ->setLastLogin(new DateTimeImmutable())
            ->setLastIpAddress($request->getClientIp())
            ->setLastUserAgent($request->headers->get('User-Agent'));

        $this->entityManager->flush();
    }
}
