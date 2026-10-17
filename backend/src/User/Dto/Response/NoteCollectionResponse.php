<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class NoteCollectionResponse
{
    /** @param list<NoteResponse> $items */
    public function __construct(public array $items)
    {
    }
}
