<?php

declare(strict_types=1);

namespace App\User\Fixture;

use App\User\Entity\User;
use App\User\Entity\UserChecklistItem;
use App\User\Entity\UserNote;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class UserChecklistItemFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $checklistItem = new UserChecklistItem(
            user: $this->getReference(UserFixture::ADMIN_USER_REFERENCE, User::class),
            item: 'Old hecklist item',
            checked: true,
        );

        $checklistItem2 = new UserChecklistItem(
            user: $this->getReference(UserFixture::ADMIN_USER_REFERENCE, User::class),
            item: 'Checklist item',
            checked: false,
        );

        $manager->persist($checklistItem);
        $manager->persist($checklistItem2);
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
