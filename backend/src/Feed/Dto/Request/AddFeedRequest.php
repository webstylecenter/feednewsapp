<?php

declare(strict_types=1);

namespace App\Feed\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AddFeedRequest
{
    #[Assert\Url(protocols: ['http', 'https'])]
    public string $feedUrl = '';

    #[Assert\Url(protocols: ['http', 'https'])]
    public string $websiteUrl = '';

    #[Assert\Url(protocols: ['http', 'https'])]
    public string $url = '';

    #[Assert\Positive]
    public ?int $category = null;

    #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/')]
    public ?string $color = null;

    #[Assert\Length(max: 64)]
    public ?string $icon = null;

    public bool $autoPin = false;
}
