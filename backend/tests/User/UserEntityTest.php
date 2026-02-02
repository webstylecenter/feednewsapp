<?php

declare(strict_types=1);

namespace App\Tests\User;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserEntityTest extends TestCase
{
    public function testUserFeedsCollectionIsInitializedAndMaintainsInverse(): void
    {
        $user = new User(
            email: 'test@example.com',
            name: 'Test User',
            password: 'hashed',
            hideXFrameNotice: false,
            enabled: true,
        );

        self::assertCount(0, $user->getUserFeeds());

        $feed = new Feed(null, 'Feed', '#fff', 'https://example.com/feed');
        $feedUser = new FeedUser($user, $feed);

        $user->addUserFeed($feedUser);

        self::assertCount(1, $user->getUserFeeds());
        self::assertSame($user, $feedUser->getUser());
    }
}
