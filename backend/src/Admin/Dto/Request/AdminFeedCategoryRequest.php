<?php

declare(strict_types=1);

namespace App\Admin\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AdminFeedCategoryRequest
{
    #[Assert\Uuid]
    public ?string $category = null;
}
