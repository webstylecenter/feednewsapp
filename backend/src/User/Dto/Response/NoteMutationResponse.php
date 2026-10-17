<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class NoteMutationResponse
{
    public function __construct(
        public NoteResponse $item,
        public string $status = 'success',
    ) {
    }
}
