<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Request\UserRequest;
use App\User\Dto\Response\UserCollectionResponse;
use App\User\Dto\Response\UserItemResponse;
use App\User\Entity\User;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class UserMapper
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

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

    public function toEntity(UserRequest $userRequest): User
    {
        $hashed = $this->passwordHasherFactory
            ->getPasswordHasher(User::class)
            ->hash($userRequest->password);

        return new User(
            email: $userRequest->email,
            name: $userRequest->name,
            password: $hashed,
            hideXFrameNotice: false,
            enabled: true,
            avatar: $userRequest->avatar,
        );
    }
}
