<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Shared\Http\NativeHttpClient;
use Throwable;

final readonly class YouTubeVideoFilter
{
    public function __construct(private NativeHttpClient $httpClient)
    {
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
            $response = $this->httpClient->head('https://www.youtube.com/shorts/' . $videoId);
        } catch (Throwable) {
            return false;
        }

        if ($response->statusCode < 200 || $response->statusCode >= 400) {
            return false;
        }

        $path = parse_url($response->url, PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/shorts/');
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
