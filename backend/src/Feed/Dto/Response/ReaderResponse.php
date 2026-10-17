<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class ReaderResponse
{
    public function __construct(
        public string $url,
        public string $html,
        public ?string $siteName,
        public ?string $mainImage,
        public string $status = 'success',
    ) {
    }
}
