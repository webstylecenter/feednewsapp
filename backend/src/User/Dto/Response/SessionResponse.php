<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class SessionResponse
{
    public function __construct(
        public ?CurrentUserResponse $user,
        public bool $firstVisit,
        public string $csrfToken,
        public OAuthAvailabilityResponse $oauth,
    ) {
    }
}
