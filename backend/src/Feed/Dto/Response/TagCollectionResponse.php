<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class TagCollectionResponse
{
    /** @param list<TagResponse> $items */
    public function __construct(public array $items)
    {
    }
}
