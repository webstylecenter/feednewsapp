<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\User\Dto\Response\UserCollectionResponse;
use App\User\Dto\Response\UserItemResponse;
use App\User\Provider\UserGetCollectionProvider;
use App\User\Provider\UserGetItemProvider;

#[ApiResource(operations: [
    new GetCollection(
        uriTemplate: '/users',
        paginationClientEnabled: false,
        paginationClientItemsPerPage: false,
        security: "is_granted('ROLE_ADMIN')",
        output: UserCollectionResponse::class,
        provider: UserGetCollectionProvider::class,
    ),
    new Get(
        uriTemplate: '/users/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        security: "is_granted('ROLE_ADMIN')",
        output: UserItemResponse::class,
        provider: UserGetItemProvider::class,
    ),
])]
final class UserDto
{
    #[ApiProperty(identifier: true)]
    public ?string $uuid = null;
}
