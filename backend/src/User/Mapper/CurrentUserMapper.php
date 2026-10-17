<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Response\CurrentUserResponse;
use App\User\Entity\User;

final class CurrentUserMapper
{
    public function toResponse(User $user): CurrentUserResponse
    {
        return new CurrentUserResponse(
            uuid: $user->getUuid()->toRfc4122(),
            email: $user->getEmail(),
            name: $user->getName(),
            avatar: $user->getAvatar(),
            isAdmin: $user->isAdmin(),
            hideXFrameNotice: $user->isHideXFrameNotice(),
            lastLogin: $user->getLastLogin()?->format(DATE_ATOM),
        );
    }
}
