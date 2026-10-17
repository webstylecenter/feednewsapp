<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Response\UserCollectionResponse;
use App\User\Dto\Response\UserItemResponse;
use App\User\Entity\User;
use RuntimeException;

final readonly class UserMapper
{
    public function toCollectionResponse(User $user): UserCollectionResponse
    {
        if ($user->getId() === null) {
            throw new RuntimeException('User has no identifier (id is null)');
        }

        return new UserCollectionResponse(
            id: $user->getId(),
            name: $user->getName(),
            enabled: $user->isEnabled(),
            isAdmin: $user->isAdmin(),
            avatar: $user->getAvatar(),
            lastLogin: $user->getLastLogin(),
        );
    }

    public function toItemResponse(User $user): UserItemResponse
    {
        if ($user->getId() === null) {
            throw new RuntimeException('User has no identifier (id is null)');
        }

        return new UserItemResponse(
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
