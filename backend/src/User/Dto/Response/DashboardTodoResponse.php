<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

final readonly class DashboardTodoResponse
{
    public function __construct(
        public string $uuid,
        public string $item,
        public bool $checked,
    ) {
    }
}
