<?php

declare(strict_types=1);

namespace App\Admin\Dto\Response;

final readonly class AdminDashboardResponse
{
    /**
     * @param list<AdminUserResponse> $users
     * @param list<AdminFeedResponse> $feeds
     * @param list<AdminFeedErrorResponse> $errors
     */
    public function __construct(
        public array $users,
        public array $feeds,
        public array $errors,
    ) {
    }
}
