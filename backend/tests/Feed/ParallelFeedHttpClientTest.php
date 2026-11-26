<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Shared\Http\FeedHttpTransfer;
use App\Shared\Http\ParallelFeedHttpClient;
use App\Shared\Http\PublicUrlGuard;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ParallelFeedHttpClientTest extends TestCase
{
    public function testEmptyBatchReturnsNoResults(): void
    {
        $client = new ParallelFeedHttpClient(new PublicUrlGuard());

        self::assertSame([], $client->getMany([]));
    }

    public function testUnsafeRequestsFailIndividuallyAndRetainTheirKeys(): void
    {
        $client = new ParallelFeedHttpClient(new PublicUrlGuard());

        $results = $client->getMany([
            3 => ['url' => 'http://127.0.0.1/feed', 'headers' => []],
            7 => ['url' => 'http://169.254.169.254/feed', 'headers' => []],
            9 => ['url' => 'http://[::1]/feed', 'headers' => []],
            11 => ['url' => 'http://127.1/feed', 'headers' => []],
        ]);

        self::assertSame([3, 7, 9, 11], array_keys($results));
        foreach ($results as $result) {
            self::assertInstanceOf(InvalidArgumentException::class, $result['response']);
            self::assertGreaterThanOrEqual(0, $result['seconds']);
        }
    }

    public function testConcurrencyIsBounded(): void
    {
        $client = new ParallelFeedHttpClient(new PublicUrlGuard());

        $this->expectException(RuntimeException::class);
        $client->getMany(array_fill(0, 11, ['url' => 'https://example.com/feed', 'headers' => []]));
    }

    public function testBodySizeLimitIsEnforcedDuringDownload(): void
    {
        $transfer = new FeedHttpTransfer();
        $handle = curl_init();
        self::assertNotFalse($handle);

        self::assertSame(2_000_000, $transfer->receiveBody($handle, str_repeat('x', 2_000_000)));
        self::assertSame(0, $transfer->receiveBody($handle, 'x'));
        self::assertTrue($transfer->tooLarge);
        self::assertSame(2_000_000, strlen($transfer->body));
    }

    public function testHeadersAreCaseInsensitiveAndBounded(): void
    {
        $transfer = new FeedHttpTransfer();
        $handle = curl_init();
        self::assertNotFalse($handle);

        $transfer->receiveHeader($handle, "HTTP/1.1 100 Continue\r\n");
        $transfer->receiveHeader($handle, "X-Old: value\r\n");
        $transfer->receiveHeader($handle, "HTTP/1.1 304 Not Modified\r\n");
        $transfer->receiveHeader($handle, "ETag: \"abc\"\r\n");

        self::assertSame(['etag' => ['"abc"']], $transfer->headers);
        self::assertSame(0, $transfer->receiveHeader($handle, str_repeat('x', 65_536)));
        self::assertTrue($transfer->tooLarge);
    }
}
