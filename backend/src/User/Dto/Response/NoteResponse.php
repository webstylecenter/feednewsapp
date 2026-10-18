<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class NoteResponse
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $content,
        public int $position,
    ) {
    }
}
