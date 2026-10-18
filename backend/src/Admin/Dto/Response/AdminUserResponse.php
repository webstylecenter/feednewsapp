<?php

declare(strict_types=1);

namespace App\Admin\Dto\Response;

final readonly class AdminUserResponse
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $email,
        public bool $enabled,
        public bool $isAdmin,
        public ?string $lastLogin,
        public string $createdAt,
    ) {
    }
}
