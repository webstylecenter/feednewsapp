<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\User\Dto\Request\CreateChecklistItemRequest;
use App\User\Dto\Response\ChecklistCollectionResponse;
use App\User\Dto\Response\ChecklistMutationResponse;
use App\User\Processor\CreateChecklistItemProcessor;
use App\User\Processor\ToggleChecklistItemProcessor;
use App\User\Provider\ChecklistCollectionProvider;

#[ApiResource(operations: [
    new Get(uriTemplate: '/checklist', output: ChecklistCollectionResponse::class, provider: ChecklistCollectionProvider::class),
    new Post(
        uriTemplate: '/checklist',
        input: CreateChecklistItemRequest::class,
        output: ChecklistMutationResponse::class,
        read: false,
        processor: CreateChecklistItemProcessor::class,
        status: 201,
    ),
    new Patch(
        uriTemplate: '/checklist/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        input: false,
        output: ChecklistMutationResponse::class,
        read: false,
        processor: ToggleChecklistItemProcessor::class,
    ),
])]
final class ChecklistDto
{
    #[ApiProperty(identifier: true)]
    public ?string $uuid = null;
}
