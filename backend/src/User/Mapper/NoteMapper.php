<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Dto\Response\NoteResponse;
use App\User\Entity\UserNote;

final class NoteMapper
{
    public function toResponse(UserNote $note): NoteResponse
    {
        return new NoteResponse(
            uuid: $note->getUuid()->toRfc4122(),
            name: $note->getName(),
            content: $note->getContent() ?? '',
            position: $note->getPosition(),
        );
    }
}
