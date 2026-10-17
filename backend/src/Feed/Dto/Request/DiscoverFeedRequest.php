<?php

declare(strict_types=1);

namespace App\Feed\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class DiscoverFeedRequest
{
    #[Assert\Url(protocols: ['http', 'https'])]
    public string $feedUrl = '';

    #[Assert\Url(protocols: ['http', 'https'])]
    public string $websiteUrl = '';

    #[Assert\Url(protocols: ['http', 'https'])]
    public string $url = '';
}
