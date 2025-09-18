<?php

declare(strict_types=1);

namespace App\User\Fixture;

use App\User\Entity\User;
use App\User\Entity\UserNote;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class UserNoteFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $note = new UserNote(
            user: $this->getReference(UserFixture::ADMIN_USER_REFERENCE, User::class),
            name: 'Example note by Admin user',
            position: 1,
            content: 'This is example note by Admin user.',
        );

        $note2 = new UserNote(
            user: $this->getReference(UserFixture::ADMIN_USER_REFERENCE, User::class),
            name: 'Second example note by Admin user',
            position: 0,
            content: 'This is example note by Admin user.',
        );

        $manager->persist($note);
        $manager->persist($note2);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['user'];
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class
        ];
    }
}
