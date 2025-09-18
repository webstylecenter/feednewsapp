<?php

declare(strict_types=1);

namespace App\Feed\Fixture;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FeedFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const FEEDS = [
        ['name' => 'Symfony Blog',
            'color' => '#1e90ff',
            'url' => 'https://symfony.com/blog/rss.xml',
            'category' => 0],
        ['name' => 'Hacker News',
            'color' => '#ff6600',
            'url' => 'https://hnrss.org/frontpage',
            'category' => 1],
        ['name' => 'PC Gamer',
            'color' => '#c2185b',
            'url' => 'https://www.pcgamer.com/rss/',
            'category' => 2],
        ['name' => 'Bloomberg Tech',
            'color' => '#6a1b9a',
            'url' => 'https://www.bloomberg.com/feeds/bbiztech.rss',
            'category' => 3],
        ['name' => 'NASA News',
            'color' => '#0b3d91',
            'url' => 'https://www.nasa.gov/rss/dyn/breaking_news.rss',
            'category' => 4],
        ['name' => 'Lifehacker',
            'color' => '#2e7d32',
            'url' => 'https://lifehacker.com/rss',
            'category' => 5],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::FEEDS as $i => $data) {
            /** @var FeedCategory $category */
            $category = $this->getReference('feed-category-' . $data['category'], FeedCategory::class);
            $feed = new Feed(
                category: $category,
                name: $data['name'],
                color: $data['color'],
                url: $data['url'],
            );

            $manager->persist($feed);
            $this->addReference('feed-' . $i, $feed);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['feed'];
    }

    public function getDependencies(): array
    {
        return [FeedCategoryFixture::class];
    }
}
