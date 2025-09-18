<?php

declare(strict_types=1);

namespace App\Feed\Fixture;

use App\Feed\Entity\FeedUser;
use App\Feed\Entity\Feed;
use App\User\Entity\User;
use App\User\Fixture\UserFixture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FeedUserFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(UserFixture::ADMIN_USER_REFERENCE, User::class);

        for ($f = 0; $f < count(FeedFixture::FEEDS); $f++) {
            /** @var Feed $feed */
            $feed = $this->getReference('feed-' . $f, Feed::class);

            $feedUser = new FeedUser(
                user: $admin,
                feed: $feed,
                icon: null,
                color: null,
                autoPin: $f % 3 === 0,
            );

            $manager->persist($feedUser);
            $this->addReference('feed-user-' . $f, $feedUser);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['feed'];
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, FeedFixture::class];
    }
}
