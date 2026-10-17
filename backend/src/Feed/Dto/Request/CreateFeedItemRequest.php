<?php

declare(strict_types=1);

namespace App\Feed\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateFeedItemRequest
{
    #[Assert\NotBlank]
    #[Assert\Url(protocols: ['http', 'https'])]
    public string $url = '';

    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 500)]
    public ?string $description = null;
}
