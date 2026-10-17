<?php

declare(strict_types=1);

namespace App\Shared\Http;

use InvalidArgumentException;

final class PublicUrlGuard
{
    public function normalize(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            throw new InvalidArgumentException('URL cannot be empty.');
        }

        if (preg_match('~^https?://~i', $url) !== 1) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Invalid URL.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only HTTP and HTTPS URLs are supported.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('URLs containing credentials are not allowed.');
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            throw new InvalidArgumentException('Local URLs are not allowed.');
        }

        $this->assertPublicHost($host);

        return $url;
    }

    private function assertPublicHost(string $host): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $this->assertPublicIp($host);
            return;
        }

        $addresses = [];
        if (function_exists('dns_get_record')) {
            $records = dns_get_record($host, DNS_A | DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    $address = $record['ip'] ?? $record['ipv6'] ?? null;
                    if (is_string($address)) {
                        $addresses[] = $address;
                    }
                }
            }
        }

        if ($addresses === []) {
            $ipv4Addresses = gethostbynamel($host);
            if (is_array($ipv4Addresses)) {
                $addresses = $ipv4Addresses;
            }
        }

        if ($addresses === []) {
            throw new InvalidArgumentException('The hostname could not be resolved.');
        }

        foreach (array_unique($addresses) as $address) {
            $this->assertPublicIp($address);
        }
    }

    private function assertPublicIp(string $address): void
    {
        $validPublicIp = filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );

        if ($validPublicIp === false) {
            throw new InvalidArgumentException('Private or reserved network addresses are not allowed.');
        }
    }
}
