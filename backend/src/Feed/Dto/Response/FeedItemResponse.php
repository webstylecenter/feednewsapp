<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class FeedItemResponse
{
    public function __construct(
        public string $uuid,
        public string $title,
        public string $description,
        public string $url,
        public string $color,
        public string $feedIcon,
        public string $feedName,
        public bool $pinned,
        public bool $viewed,
        public ?string $openedAt,
        public string $createdAt,
        public ?string $tagUuid,
    ) {
    }
}
