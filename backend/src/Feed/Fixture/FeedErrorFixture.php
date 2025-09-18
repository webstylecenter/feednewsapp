<?php

declare(strict_types=1);

namespace App\Feed\Fixture;

use App\Feed\Entity\FeedError;
use App\Feed\Entity\Feed;
use App\Feed\Enum\FeedErrorType;
use App\User\Entity\User;
use App\User\Fixture\UserFixture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FeedErrorFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(UserFixture::ADMIN_USER_REFERENCE, User::class);
        foreach ([0, 2, 4] as $idx) {
            /** @var Feed $feed */
            $feed = $this->getReference('feed-' . $idx, Feed::class);

            $error = new FeedError(
                type: FeedErrorType::ERROR,
                user: $admin,
                feed: $feed,
                exception: 'cURL error 28: Operation timed out after 5000 milliseconds with 0 bytes received'
            );

            $manager->persist($error);
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
