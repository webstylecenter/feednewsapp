<?php

declare(strict_types=1);

namespace App\Admin\Dto\Response;

final readonly class AdminFeedErrorResponse
{
    public function __construct(
        public string $type,
        public int $occurrences,
        public string $message,
        public ?string $user,
        public ?string $feed,
        public string $updatedAt,
    ) {
    }
}
