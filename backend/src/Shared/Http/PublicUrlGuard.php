<?php

declare(strict_types=1);

namespace App\Shared\Http;

use InvalidArgumentException;

final class PublicUrlGuard
{
    public function normalize(string $url): string
    {
        return $this->resolveForConnection($url)['url'];
    }

    /** @return array{url: string, resolve: list<string>} */
    public function resolveForConnection(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            throw new InvalidArgumentException('URL cannot be empty.');
        }

        if (preg_match('~^https?://~i', $url) !== 1) {
            $url = 'https://' . $url;
        }

        if (preg_match('~[\\x00-\\x20\\x7f]~', $url) === 1 || str_contains($url, '\\')) {
            throw new InvalidArgumentException('Invalid URL.');
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

        $originalHost = strtolower($parts['host']);
        $host = rtrim(trim($originalHost, '[]'), '.');
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            throw new InvalidArgumentException('Local URLs are not allowed.');
        }

        $addresses = $this->publicAddresses($host);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($port < 1) {
            throw new InvalidArgumentException('Invalid URL port.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return ['url' => $url, 'resolve' => []];
        }

        $pinnedAddresses = array_map(
            static fn (string $address): string => str_contains($address, ':') ? '[' . $address . ']' : $address,
            $addresses,
        );

        return [
            'url' => $url,
            'resolve' => [$originalHost . ':' . $port . ':' . implode(',', $pinnedAddresses)],
        ];
    }

    /** @return list<string> */
    private function publicAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $this->assertPublicIp($host);
            return [$host];
        }

        if (
            preg_match('~^[a-z0-9.-]+$~D', $host) !== 1
            || preg_match('~^(?:[0-9]+|0x[0-9a-f]+)(?:\\.(?:[0-9]+|0x[0-9a-f]+))*$~D', $host) === 1
        ) {
            throw new InvalidArgumentException('Invalid hostname.');
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

        $addresses = array_values(array_unique($addresses));
        foreach ($addresses as $address) {
            $this->assertPublicIp($address);
        }

        return $addresses;
    }

    private function assertPublicIp(string $address): void
    {
        $validPublicIp = filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_GLOBAL_RANGE | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );

        if ($validPublicIp === false) {
            throw new InvalidArgumentException('Private or reserved network addresses are not allowed.');
        }
    }
}
