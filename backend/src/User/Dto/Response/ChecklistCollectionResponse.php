<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class ChecklistCollectionResponse
{
    /** @param list<ChecklistItemResponse> $items */
    public function __construct(public array $items)
    {
    }
}
