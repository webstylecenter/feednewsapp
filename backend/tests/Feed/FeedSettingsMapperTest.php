<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedUser;
use App\Feed\Mapper\FeedSettingsMapper;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class FeedSettingsMapperTest extends TestCase
{
    public function testSubscriptionResponseUsesSubscriptionUuid(): void
    {
        $user = new User(
            email: 'test@example.com',
            name: 'Test User',
            password: 'hashed',
            hideXFrameNotice: false,
            enabled: true,
        );
        $feed = new Feed(null, 'Feed', '#ffffff', 'https://example.com/feed');
        $subscription = new FeedUser($user, $feed);

        $response = (new FeedSettingsMapper())->toSubscriptionResponse($subscription);

        self::assertSame($subscription->getUuid()->toRfc4122(), $response->uuid);
        self::assertSame($feed->getUuid()->toRfc4122(), $response->feed->uuid);
        self::assertNotSame($response->uuid, $response->feed->uuid);
    }
}
