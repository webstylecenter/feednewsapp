<?php

declare(strict_types=1);

namespace App\User\OAuth;

use App\User\Entity\User;
use App\User\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class OAuthUserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findOrCreate(OAuthProfile $profile): User
    {
        $email = strtolower($profile->email);
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $user = new User($email, $profile->name, '', false, true, $profile->avatar);
            $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
            $this->entityManager->persist($user);
        } else {
            $user->setName($profile->name)->setAvatar($profile->avatar);
        }

        $this->entityManager->flush();
        return $user;
    }
}
