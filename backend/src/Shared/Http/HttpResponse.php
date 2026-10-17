<?php

declare(strict_types=1);

namespace App\Shared\Http;

final readonly class HttpResponse
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
        public string $url,
    ) {
    }

    public function firstHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }
}
