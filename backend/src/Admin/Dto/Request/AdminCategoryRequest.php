<?php

declare(strict_types=1);

namespace App\Admin\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AdminCategoryRequest
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255)]
    public string $name = '';
}
