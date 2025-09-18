<?php

declare(strict_types=1);

namespace App\Feed\Fixture;

use App\Feed\Entity\FeedUserItem;
use App\Feed\Entity\FeedUser;
use App\Feed\Entity\FeedItem;
use App\Feed\Entity\FeedTag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FeedUserItemFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $totalItems = 0;
        for ($f = 0; $f < count(FeedFixture::FEEDS); $f++) {
            $totalItems += 8;
        }

        $pinnedEvery = 8;
        $viewedEvery = 2;

        for ($i = 0; $i < $totalItems; $i++) {
            /** @var FeedItem $item */
            $item = $this->getReference('feed-item-' . $i, FeedItem::class);

            $feedIndex = intdiv($i, 8);
            /** @var FeedUser $userFeed */
            $userFeed = $this->getReference('feed-user-' . $feedIndex, FeedUser::class);

            $feedUserItem = new FeedUserItem(
                user: $userFeed->getUser(),
                feedItem: $item,
                userFeed: $userFeed,
                tag: null
            );

            $feedUserItem
                ->setViewed($i % $viewedEvery === 0)
                ->setPinned($i % $pinnedEvery === 0);

            if ($i % 5 === 0 && $this->hasReference('feed-tag-0', FeedTag::class)) {
                /** @var FeedTag $tag */
                $tag = $this->getReference('feed-tag-' . ($i % 3), FeedTag::class);
                $feedUserItem->setTag($tag);
            }

            $manager->persist($feedUserItem);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['feed'];
    }

    public function getDependencies(): array
    {
        return [
            FeedItemFixture::class,
            FeedUserFixture::class,
            FeedTagFixture::class,
        ];
    }
}
