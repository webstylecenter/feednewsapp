<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Response\ChecklistItemResponse;
use App\User\Entity\UserChecklistItem;

final class ChecklistMapper
{
    public function toResponse(UserChecklistItem $item): ChecklistItemResponse
    {
        return new ChecklistItemResponse(
            uuid: $item->getUuid()->toRfc4122(),
            item: $item->getItem(),
            checked: $item->getChecked(),
        );
    }
}
