<?php

declare(strict_types=1);

namespace App\Admin\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use App\Admin\Dto\Response\AdminDashboardResponse;
use App\Admin\Processor\DeleteAdminUserProcessor;
use App\Admin\Provider\AdminDashboardProvider;
use App\Shared\Dto\Response\StatusResponse;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/admin',
        output: AdminDashboardResponse::class,
        provider: AdminDashboardProvider::class,
        security: "is_granted('ROLE_ADMIN')",
    ),
    new Delete(
        uriTemplate: '/admin/users/{id}',
        requirements: ['id' => '\\d+'],
        output: StatusResponse::class,
        read: false,
        processor: DeleteAdminUserProcessor::class,
        status: 200,
        security: "is_granted('ROLE_ADMIN')",
    ),
])]
final class AdminDto
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;
}
