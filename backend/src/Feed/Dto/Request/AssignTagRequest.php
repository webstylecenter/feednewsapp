<?php

declare(strict_types=1);

namespace App\Feed\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AssignTagRequest
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $itemUuid = '';

    #[Assert\Uuid]
    public ?string $tagUuid = null;
}
