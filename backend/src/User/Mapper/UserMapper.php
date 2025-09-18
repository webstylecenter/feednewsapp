<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Response\UserResponse;
use App\User\Entity\User;
use RuntimeException;

final readonly class UserMapper
{
    public function toResponse(User $user): UserResponse
    {
        if ($user->getId() === null) {
            throw new RuntimeException('User has no identifier (id is null)');
        }

        return new UserResponse(
            id: $user->getId(),
            name: $user->getName(),
            email: $user->getEmail(),
            hideXFrameNotice: $user->isHideXFrameNotice(),
            enabled: $user->isEnabled(),
            isAdmin: $user->isAdmin(),
            avatar: $user->getAvatar(),
            roles: $user->getRoles(),
            lastLogin: $user->getLastLogin(),
        );
    }
}
