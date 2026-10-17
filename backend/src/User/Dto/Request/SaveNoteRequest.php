<?php

declare(strict_types=1);

namespace App\User\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class SaveNoteRequest
{
    #[Assert\Positive]
    public ?int $id = null;

    #[Assert\Length(max: 255)]
    public string $name = 'Note';

    public int $position = 0;

    public string $content = '';

    public ?string $note = null;
}
