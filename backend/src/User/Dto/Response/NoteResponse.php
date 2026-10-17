<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class NoteResponse
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $content,
        public int $position,
    ) {
    }
}
