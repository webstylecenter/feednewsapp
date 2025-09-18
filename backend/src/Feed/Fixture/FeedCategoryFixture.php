<?php

declare(strict_types=1);

namespace App\Feed\Fixture;

use App\Feed\Entity\FeedCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class FeedCategoryFixture extends Fixture implements FixtureGroupInterface
{
    public const CATEGORIES = ['Tech', 'News', 'Gaming', 'Business', 'Science', 'Lifestyle'];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $i => $name) {
            $cat = new FeedCategory(name: $name);
            $manager->persist($cat);
            $this->addReference('feed-category-' . $i, $cat);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['feed'];
    }
}
