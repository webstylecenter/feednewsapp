<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Shared\Http\NativeHttpClient;
use RuntimeException;

final readonly class FramePolicyService
{
    public function __construct(private NativeHttpClient $httpClient)
    {
    }

    /** @return array{blocked: bool, uncertain: bool} */
    public function check(string $url): array
    {
        try {
            $response = $this->httpClient->head($url);
            if (in_array($response->statusCode, [0, 405, 501], true)) {
                $response = $this->httpClient->get($url, ['Range' => 'bytes=0-0'], 0);
            }
        } catch (RuntimeException) {
            return ['blocked' => false, 'uncertain' => true];
        }

        $xFrameOptions = strtolower((string) $response->firstHeader('x-frame-options'));
        if ($xFrameOptions !== '' && (str_contains($xFrameOptions, 'deny') || str_contains($xFrameOptions, 'sameorigin'))) {
            return ['blocked' => true, 'uncertain' => false];
        }

        $contentSecurityPolicy = strtolower((string) $response->firstHeader('content-security-policy'));
        if ($contentSecurityPolicy !== '' && preg_match('/frame-ancestors\s+([^;]+)/', $contentSecurityPolicy, $matches) === 1) {
            $ancestors = trim($matches[1]);
            if ($ancestors === "'none'" || $ancestors === "'self'") {
                return ['blocked' => true, 'uncertain' => false];
            }
        }

        return ['blocked' => false, 'uncertain' => false];
    }
}
