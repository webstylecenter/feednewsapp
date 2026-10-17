<?php

declare(strict_types=1);

namespace App\Shared\Request;

use JsonException;
use Symfony\Component\HttpFoundation\Request;

final class RequestData
{
    /**
     * @return array<string, mixed>
     */
    public static function all(Request $request): array
    {
        $data = $request->request->all();
        if ($data !== [] || !str_contains((string) $request->headers->get('Content-Type'), 'application/json')) {
            return $data;
        }

        try {
            $decoded = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? $default;
        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /** @param array<string, mixed> $data */
    public static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $data */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return filter_var($data[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
