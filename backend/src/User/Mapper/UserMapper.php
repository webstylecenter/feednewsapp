<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Response\UserCollectionResponse;
use App\User\Dto\Response\UserItemResponse;
use App\User\Entity\User;

final readonly class UserMapper
{
    public function toCollectionResponse(User $user): UserCollectionResponse
    {
        return new UserCollectionResponse(
            uuid: $user->getUuid()->toRfc4122(),
            name: $user->getName(),
            enabled: $user->isEnabled(),
            isAdmin: $user->isAdmin(),
            avatar: $user->getAvatar(),
            lastLogin: $user->getLastLogin(),
        );
    }

    public function toItemResponse(User $user): UserItemResponse
    {
        return new UserItemResponse(
            uuid: $user->getUuid()->toRfc4122(),
            name: $user->getName(),
            email: $user->getEmail(),
            hideXFrameNotice: $user->isHideXFrameNotice(),
            enabled: $user->isEnabled(),
            isAdmin: $user->isAdmin(),
            avatar: $user->getAvatar(),
            roles: array_values($user->getRoles()),
            lastLogin: $user->getLastLogin(),
        );
    }
}
