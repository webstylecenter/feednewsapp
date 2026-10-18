<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

use DateTimeImmutable;

final readonly class UserCollectionResponse
{
    public function __construct(
        public string $uuid,
        public string $name,
        public bool $enabled,
        public bool $isAdmin,
        public ?string $avatar,
        public ?DateTimeImmutable $lastLogin,
    ) {
    }
}
