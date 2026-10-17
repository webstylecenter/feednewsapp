<?php

declare(strict_types=1);

namespace App\User\Fixture;

use App\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture implements FixtureGroupInterface
{
    public const ADMIN_USER_REFERENCE = 'admin-user';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $userAdmin = new User(
            email: 'admin@feednews.dev',
            name: 'Admin',
            password: '',
            hideXFrameNotice: false,
            enabled: true,
            avatar: null,
        );
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, 'feednews-admin'));
        $userAdmin->setIsAdmin(true);

        $manager->persist($userAdmin);
        $manager->flush();

        // other fixtures can get this object using the UserFixture::ADMIN_USER_REFERENCE constant
        $this->addReference(self::ADMIN_USER_REFERENCE, $userAdmin);
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['user'];
    }
}
