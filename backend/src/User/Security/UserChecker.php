<?php

declare(strict_types=1);

namespace App\User\Security;

use App\User\Entity\User;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && $user->isEnabled() === false) {
            throw new DisabledException('This FeedNews account is disabled.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
