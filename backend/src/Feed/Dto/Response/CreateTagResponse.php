<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class CreateTagResponse
{
    public function __construct(
        public string $uuid,
        public string $status = 'success',
    ) {
    }
}
