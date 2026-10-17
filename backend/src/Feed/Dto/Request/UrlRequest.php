<?php

declare(strict_types=1);

namespace App\Feed\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UrlRequest
{
    #[Assert\NotBlank]
    #[Assert\Url(protocols: ['http', 'https'])]
    public string $url = '';
}
