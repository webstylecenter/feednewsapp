<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Response\ChecklistItemResponse;
use App\User\Entity\UserChecklistItem;
use LogicException;

final class ChecklistMapper
{
    public function toResponse(UserChecklistItem $item): ChecklistItemResponse
    {
        $id = $item->getId();
        if ($id === null) {
            throw new LogicException('Cannot map an unpersisted checklist item.');
        }

        return new ChecklistItemResponse(
            id: $id,
            uuid: $item->getUuid()->toRfc4122(),
            item: $item->getItem(),
            checked: $item->getChecked(),
        );
    }
}
