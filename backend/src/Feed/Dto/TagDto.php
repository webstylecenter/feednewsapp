<?php

declare(strict_types=1);

namespace App\Feed\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Feed\Dto\Request\AssignTagRequest;
use App\Feed\Dto\Request\CreateTagRequest;
use App\Feed\Dto\Response\CreateTagResponse;
use App\Feed\Dto\Response\TagCollectionResponse;
use App\Feed\Processor\AssignTagProcessor;
use App\Feed\Processor\CreateTagProcessor;
use App\Feed\Processor\DeleteTagProcessor;
use App\Feed\Provider\TagCollectionProvider;
use App\Shared\Dto\Response\StatusResponse;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/tags',
        output: TagCollectionResponse::class,
        provider: TagCollectionProvider::class,
    ),
    new Post(
        uriTemplate: '/tags',
        input: CreateTagRequest::class,
        output: CreateTagResponse::class,
        read: false,
        processor: CreateTagProcessor::class,
        status: 201,
    ),
    new Post(
        uriTemplate: '/tags/assign',
        input: AssignTagRequest::class,
        output: StatusResponse::class,
        read: false,
        processor: AssignTagProcessor::class,
        status: 200,
    ),
    new Delete(
        uriTemplate: '/tags/{id}',
        requirements: ['id' => '\\d+'],
        output: StatusResponse::class,
        read: false,
        processor: DeleteTagProcessor::class,
        status: 200,
    ),
])]
final class TagDto
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;
}
