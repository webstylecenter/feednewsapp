<?php

declare(strict_types=1);

namespace App\User\Fixture;

use App\User\Entity\User;
use App\User\Entity\UserChecklistItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class UserChecklistItemFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $checklistItem = new UserChecklistItem(
            user: $this->getReference(UserFixture::ADMIN_USER_REFERENCE, User::class),
            item: 'Old checklist item',
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

    #[Override]
    public static function getGroups(): array
    {
        return ['user'];
    }

    #[Override]
    public function getDependencies(): array
    {
        return [
            UserFixture::class
        ];
    }
}
