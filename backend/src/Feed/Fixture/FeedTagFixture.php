<?php

declare(strict_types=1);

namespace App\Feed\Fixture;

use App\Feed\Entity\FeedTag;
use App\User\Entity\User;
use App\User\Fixture\UserFixture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FeedTagFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(UserFixture::ADMIN_USER_REFERENCE, User::class);

        $tags = [
            ['name' => 'Important', 'color' => '#c62828'],
            ['name' => 'Research', 'color' => '#1565c0'],
            ['name' => 'Later', 'color' => '#2e7d32'],
        ];

        foreach ($tags as $i => $tag) {
            $feedTag = new FeedTag(
                user: $admin,
                name: $tag['name'],
                color: $tag['color']
            );

            $manager->persist($feedTag);
            $this->addReference('feed-tag-' . $i, $feedTag);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['feed'];
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }
}
