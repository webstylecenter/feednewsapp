<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedItem;
use App\Feed\Entity\FeedUser;
use App\Feed\Entity\FeedUserItem;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class FeedUserItemRelationTest extends TestCase
{
    public function testUserFeedItemRelationIsKeptInSync(): void
    {
        $user = new User(
            email: 'test@example.com',
            name: 'Test User',
            password: 'hashed',
            hideXFrameNotice: false,
            enabled: true,
        );

        $feed = new Feed(null, 'Feed', '#fff', 'https://example.com/feed');
        $userFeed = new FeedUser($user, $feed);
        $feedItem = new FeedItem('Title', null, $feed, 'guid-1', 'https://example.com/item');

        $item = new FeedUserItem($user, $feedItem, $userFeed, null);
        $userFeed->addUserFeedItem($item);

        self::assertSame($userFeed, $item->getUserFeed());
        self::assertTrue($userFeed->getItems()->contains($item));

        $userFeed->removeUserFeedItem($item);

        self::assertNull($item->getUserFeed());
        self::assertFalse($userFeed->getItems()->contains($item));
    }
}
