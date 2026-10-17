<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class CurrentUserResponse
{
    public function __construct(
        public string $uuid,
        public string $email,
        public string $name,
        public ?string $avatar,
        public bool $isAdmin,
        public bool $hideXFrameNotice,
        public ?string $lastLogin,
    ) {
    }
}
