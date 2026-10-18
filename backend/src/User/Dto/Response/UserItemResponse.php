<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

use DateTimeImmutable;

final readonly class UserItemResponse
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $uuid,
        public string $name,
        public string $email,
        public bool $hideXFrameNotice,
        public bool $enabled,
        public bool $isAdmin,
        public ?string $avatar,
        public array $roles,
        public ?DateTimeImmutable $lastLogin,
    ) {
    }
}
