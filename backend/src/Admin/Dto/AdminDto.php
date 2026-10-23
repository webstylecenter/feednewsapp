<?php

declare(strict_types=1);

namespace App\Admin\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Admin\Dto\Request\AdminCategoryRequest;
use App\Admin\Dto\Request\AdminFeedCategoryRequest;
use App\Admin\Dto\Response\AdminDashboardResponse;
use App\Admin\Processor\CreateAdminCategoryProcessor;
use App\Admin\Processor\DeleteAdminCategoryProcessor;
use App\Admin\Processor\DeleteAdminFeedProcessor;
use App\Admin\Processor\DeleteAdminUserProcessor;
use App\Admin\Processor\UpdateAdminCategoryProcessor;
use App\Admin\Processor\UpdateAdminFeedCategoryProcessor;
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
        uriTemplate: '/admin/users/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        output: StatusResponse::class,
        read: false,
        processor: DeleteAdminUserProcessor::class,
        status: 200,
        security: "is_granted('ROLE_ADMIN')",
    ),
    new Post(
        uriTemplate: '/admin/categories',
        input: AdminCategoryRequest::class,
        output: StatusResponse::class,
        read: false,
        processor: CreateAdminCategoryProcessor::class,
        status: 201,
        security: "is_granted('ROLE_ADMIN')",
    ),
    new Patch(
        uriTemplate: '/admin/categories/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        input: AdminCategoryRequest::class,
        output: StatusResponse::class,
        read: false,
        processor: UpdateAdminCategoryProcessor::class,
        status: 200,
        security: "is_granted('ROLE_ADMIN')",
    ),
    new Delete(
        uriTemplate: '/admin/categories/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        output: StatusResponse::class,
        read: false,
        processor: DeleteAdminCategoryProcessor::class,
        status: 200,
        security: "is_granted('ROLE_ADMIN')",
    ),
    new Patch(
        uriTemplate: '/admin/feeds/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        input: AdminFeedCategoryRequest::class,
        output: StatusResponse::class,
        read: false,
        processor: UpdateAdminFeedCategoryProcessor::class,
        status: 200,
        security: "is_granted('ROLE_ADMIN')",
    ),
    new Delete(
        uriTemplate: '/admin/feeds/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        output: StatusResponse::class,
        read: false,
        processor: DeleteAdminFeedProcessor::class,
        status: 200,
        security: "is_granted('ROLE_ADMIN')",
    ),
])]
final class AdminDto
{
    #[ApiProperty(identifier: true)]
    public ?string $uuid = null;
}
