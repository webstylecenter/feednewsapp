<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Doctrine\Orm\State\Options as DoctrineOrmStateOptions;
use App\User\Dto\Response\UserResponse;
use App\User\Entity\User;
use App\User\Provider\UserGetCollectionProvider;

#[ApiResource(
    output: UserResponse::class,
    stateOptions: new DoctrineOrmStateOptions(entityClass: User::class)
)]
#[GetCollection(
    uriTemplate: '/users',
    paginationClientEnabled: false,
    paginationClientItemsPerPage: false,
    provider: UserGetCollectionProvider::class,
)]
final readonly class UserDto
{
}
