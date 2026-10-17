<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class DashboardNoteResponse
{
    public function __construct(
        public ?int $id,
        public string $name,
        public int $position,
        public string $content,
    ) {
    }
}
