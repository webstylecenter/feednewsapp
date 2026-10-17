<?php

declare(strict_types=1);

namespace App\Feed\Dto\Request;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final class AssignTagRequest
{
    #[Assert\Positive]
    public ?int $itemId = null;

    #[Assert\Positive]
    public ?int $tagId = null;

    #[SerializedName('user-feed-item-id')]
    #[Assert\Positive]
    public ?int $legacyItemId = null;

    #[SerializedName('tag')]
    #[Assert\Positive]
    public ?int $legacyTagId = null;
}
