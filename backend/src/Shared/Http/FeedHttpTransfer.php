<?php

declare(strict_types=1);

namespace App\Shared\Http;

use CurlHandle;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class FeedHttpTransfer
{
    private const int MAX_BODY_BYTES = 2_000_000;
    private const int MAX_HEADER_BYTES = 65_536;

    /** @var array<string, list<string>> */
    public array $headers = [];
    public string $body = '';
    public bool $tooLarge = false;
    private int $headerBytes = 0;

    public function receiveHeader(CurlHandle $handle, string $line): int
    {
        $length = strlen($line);
        $this->headerBytes += $length;
        if ($this->headerBytes > self::MAX_HEADER_BYTES) {
            $this->tooLarge = true;
            return 0;
        }

        if (str_starts_with($line, 'HTTP/')) {
            $this->headers = [];
        } elseif (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            $this->headers[strtolower(trim($name))][] = trim($value);
        }

        return $length;
    }

    public function receiveBody(CurlHandle $handle, string $data): int
    {
        if (strlen($this->body) + strlen($data) > self::MAX_BODY_BYTES) {
            $this->tooLarge = true;
            return 0;
        }

        $this->body .= $data;

        return strlen($data);
    }
}
