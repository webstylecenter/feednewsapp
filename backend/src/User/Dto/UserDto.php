<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Doctrine\Orm\State\Options as DoctrineOrmStateOptions;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\User\Dto\Request\UserRequest;
use App\User\Dto\Response\UserCollectionResponse;
use App\User\Dto\Response\UserItemResponse;
use App\User\Entity\User;
use App\User\Processor\UserPostProcessor;
use App\User\Provider\UserGetCollectionProvider;
use App\User\Provider\UserGetItemProvider;

#[ApiResource(
    output: UserCollectionResponse::class,
    stateOptions: new DoctrineOrmStateOptions(entityClass: User::class)
)]
#[GetCollection(
    uriTemplate: '/users',
    paginationClientEnabled: false,
    paginationClientItemsPerPage: false,
    provider: UserGetCollectionProvider::class,
)]
#[Get(
    uriTemplate: '/users/{id}',
    uriVariables: [
        'id' => new Link(
            fromClass: User::class,
            identifiers: ['id'],
        ),
    ],
    requirements: ['id' => '\d+'],
    output: UserItemResponse::class,
    provider: UserGetItemProvider::class,
)]
#[Post(
    uriTemplate: '/users',
    denormalizationContext: ['groups' => ['user:write']],
    input: UserRequest::class,
    output: UserItemResponse::class,
    read: false,
    processor: UserPostProcessor::class,
)]
final readonly class UserDto
{
}
