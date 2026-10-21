<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Feed\Service\YouTubeVideoFilter;
use App\Shared\Http\NativeHttpClient;
use App\Shared\Http\PublicUrlGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

final class YouTubeVideoFilterTest extends TestCase
{
    public function testExplicitShortUrlIsDetectedWithoutRemoteLookup(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::never())->method('get');

        $filter = new YouTubeVideoFilter(
            new NativeHttpClient(new PublicUrlGuard()),
            $cache,
        );

        self::assertTrue($filter->isShort('https://www.youtube.com/shorts/u06AH8OvQnQ'));
    }
}
