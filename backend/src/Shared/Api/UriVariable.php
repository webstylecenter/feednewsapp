<?php

declare(strict_types=1);

namespace App\Shared\Api;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class UriVariable
{
    /** @param array<string, mixed> $uriVariables */
    public static function positiveInt(array $uriVariables, string $name = 'id'): int
    {
        if (!array_key_exists($name, $uriVariables)) {
            throw new InvalidArgumentException(sprintf('%s is required.', $name));
        }

        $value = $uriVariables[$name];
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && $value !== '' && ctype_digit($value)) {
            $integer = (int) $value;
            if ($integer > 0) {
                return $integer;
            }
        }

        throw new InvalidArgumentException(sprintf('%s must be a positive integer.', $name));
    }

    /** @param array<string, mixed> $uriVariables */
    public static function uuid(array $uriVariables, string $name = 'uuid'): string
    {
        $value = $uriVariables[$name] ?? null;
        if (!is_string($value) || !Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('%s must be a valid UUID.', $name));
        }

        return $value;
    }

    /** @param array<string, mixed> $uriVariables */
    public static function nonEmptyString(array $uriVariables, string $name): string
    {
        $value = $uriVariables[$name] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('%s is required.', $name));
        }

        return $value;
    }
}
