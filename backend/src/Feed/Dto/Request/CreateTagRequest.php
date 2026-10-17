<?php

declare(strict_types=1);

namespace App\Feed\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateTagRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $name = '';

    #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/')]
    public string $color = '';
}
