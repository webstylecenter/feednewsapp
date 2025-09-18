<?php

declare(strict_types=1);

namespace App\User\Fixture;

use App\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class UserFixture extends Fixture implements FixtureGroupInterface
{
    public const ADMIN_USER_REFERENCE = 'admin-user';

    public function load(ObjectManager $manager): void
    {
        $userAdmin = new User(
            email: 'admin@feednews.dev',
            name: 'Admin',
            password: 'xwy0eth!PTX_tft-fcn',
            hideXFrameNotice: false,
            enabled: true,
            avatar: null,
        );

        $manager->persist($userAdmin);
        $manager->flush();

        // other fixtures can get this object using the UserFixture::ADMIN_USER_REFERENCE constant
        $this->addReference(self::ADMIN_USER_REFERENCE, $userAdmin);
    }

    public static function getGroups(): array
    {
        return ['user'];
    }
}
