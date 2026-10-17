<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class TagResponse
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $color,
        public int $count,
    ) {
    }
}
