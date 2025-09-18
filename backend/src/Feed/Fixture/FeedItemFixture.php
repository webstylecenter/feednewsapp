<?php

declare(strict_types=1);

namespace App\Feed\Fixture;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedItem;
use DateInterval;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;

final class FeedItemFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $now = new DateTime();
        $iRef = 0;

        $count = count(FeedFixture::FEEDS);
        for ($f = 0; $f < $count; $f++) {
            /** @var Feed $feed */
            $feed = $this->getReference('feed-' . $f, Feed::class);

            for ($i = 0; $i < 8; $i++) {
                $created = $now->sub(new DateInterval('P' . rand(0, 20) . 'D'));
                $title = sprintf('%s article %d', $feed->getName(), $i + 1);

                $item = new FeedItem(
                    title: $title,
                    description: 'Sample teaser for ' . $title . ' — realistic placeholder text.',
                    feed: $feed,
                    guid: hash('sha256', $feed->getUrl() . '|' . $title),
                    url: $feed->getUrl(),
                );

                $item->setCreatedAt($created);
                $item->setUpdatedAt($created);

                $manager->persist($item);
                $this->addReference('feed-item-' . $iRef, $item);
                $iRef++;
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['feed'];
    }

    public function getDependencies(): array
    {
        return [FeedFixture::class];
    }
}
