<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Response\NoteResponse;
use App\User\Entity\UserNote;
use LogicException;

final class NoteMapper
{
    public function toResponse(UserNote $note): NoteResponse
    {
        $id = $note->getId();
        if ($id === null) {
            throw new LogicException('Cannot map an unpersisted note.');
        }

        return new NoteResponse(
            id: $id,
            uuid: $note->getUuid()->toRfc4122(),
            name: $note->getName(),
            content: $note->getContent() ?? '',
            position: $note->getPosition(),
        );
    }
}
