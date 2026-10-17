<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class ChecklistMutationResponse
{
    public function __construct(
        public ChecklistItemResponse $item,
        public string $status = 'success',
    ) {
    }
}
