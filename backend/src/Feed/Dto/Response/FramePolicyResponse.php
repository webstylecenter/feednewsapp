<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FramePolicyResponse
{
    public function __construct(
        public bool $blocked,
        public bool $uncertain,
    ) {
    }
}
