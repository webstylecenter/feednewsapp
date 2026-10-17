<?php

declare(strict_types=1);

namespace App\Shared\Dto\Response;

final readonly class StatusResponse
{
    public function __construct(public string $status = 'success')
    {
    }
}
