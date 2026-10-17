<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class PageMetadataResponse
{
    public function __construct(
        public PageMetadataDataResponse $data,
        public string $status = 'success',
    ) {
    }
}
