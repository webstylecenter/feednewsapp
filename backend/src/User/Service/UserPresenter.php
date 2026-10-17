<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;

final class UserPresenter
{
    /**
     * @return array{
     *     uuid: string,
     *     email: string,
     *     name: string,
     *     avatar: string|null,
     *     isAdmin: bool,
     *     hideXFrameNotice: bool,
     *     lastLogin: string|null
     * }
     */
    public function present(User $user): array
    {
        return [
            'uuid' => $user->getUuid()->toRfc4122(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'avatar' => $user->getAvatar(),
            'isAdmin' => $user->isAdmin(),
            'hideXFrameNotice' => $user->isHideXFrameNotice(),
            'lastLogin' => $user->getLastLogin()?->format(DATE_ATOM),
        ];
    }
}
