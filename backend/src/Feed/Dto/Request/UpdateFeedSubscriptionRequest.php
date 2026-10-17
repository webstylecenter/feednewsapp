<?php

declare(strict_types=1);

namespace App\Feed\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateFeedSubscriptionRequest
{
    #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/')]
    public ?string $color = null;

    #[Assert\Length(max: 64)]
    public ?string $icon = null;

    public ?bool $autoPin = null;
}
