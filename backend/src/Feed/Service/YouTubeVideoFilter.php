<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Shared\Http\NativeHttpClient;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

final readonly class YouTubeVideoFilter
{
    private const int CACHE_TTL_SECONDS = 31_536_000;
    private const int CHECK_TIMEOUT_SECONDS = 3;

    public function __construct(
        private NativeHttpClient $httpClient,
        private CacheInterface $cache,
    ) {
    }

    public function isYouTubeFeed(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host)
            && ($host === 'youtube.com' || $host === 'www.youtube.com')
            && str_contains($url, '/feeds/videos.xml');
    }

    public function isShort(string $url): bool
    {
        $videoId = $this->videoId($url);
        if ($videoId === null) {
            return false;
        }

        try {
            return $this->cache->get(
                'youtube_short_' . $videoId,
                function (ItemInterface $item) use ($videoId): bool {
                    $response = $this->httpClient->head(
                        'https://www.youtube.com/shorts/' . $videoId,
                        timeoutSeconds: self::CHECK_TIMEOUT_SECONDS,
                    );

                    if ($response->statusCode < 200 || $response->statusCode >= 400) {
                        throw new RuntimeException(sprintf(
                            'YouTube Shorts check returned HTTP %d.',
                            $response->statusCode,
                        ));
                    }

                    $path = parse_url($response->url, PHP_URL_PATH);
                    if (!is_string($path)) {
                        throw new RuntimeException('YouTube Shorts check returned an invalid URL.');
                    }

                    $item->expiresAfter(self::CACHE_TTL_SECONDS);

                    return str_starts_with($path, '/shorts/');
                },
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function videoId(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) {
            return null;
        }

        if ($host === 'youtu.be') {
            $path = parse_url($url, PHP_URL_PATH);
            $videoId = is_string($path) ? trim($path, '/') : '';
        } elseif ($host === 'youtube.com' || str_ends_with($host, '.youtube.com')) {
            $query = parse_url($url, PHP_URL_QUERY);
            if (!is_string($query)) {
                return null;
            }

            /** @var array<string, mixed> $parameters */
            $parameters = [];
            parse_str($query, $parameters);
            $videoId = $parameters['v'] ?? null;
        } else {
            return null;
        }

        return is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $videoId) === 1
            ? $videoId
            : null;
    }
}
