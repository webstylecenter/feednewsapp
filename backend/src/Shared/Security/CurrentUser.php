<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CurrentUser
{
    public function __construct(private Security $security)
    {
    }

    public function get(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Authentication required.');
        }

        return $user;
    }
}
