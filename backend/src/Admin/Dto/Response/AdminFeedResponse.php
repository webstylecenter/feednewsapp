<?php

declare(strict_types=1);

namespace App\Admin\Dto\Response;

final readonly class AdminFeedResponse
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $url,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
