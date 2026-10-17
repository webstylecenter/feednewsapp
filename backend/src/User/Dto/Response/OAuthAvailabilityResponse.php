<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class OAuthAvailabilityResponse
{
    public function __construct(
        public bool $google,
        public bool $facebook,
    ) {
    }
}
