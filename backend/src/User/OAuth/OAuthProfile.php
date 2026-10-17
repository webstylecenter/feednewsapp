<?php

declare(strict_types=1);

namespace App\User\OAuth;

final readonly class OAuthProfile
{
    public function __construct(
        public string $email,
        public string $name,
        public ?string $avatar,
    ) {
    }
}
