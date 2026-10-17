<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Shared\Dto\Response\StatusResponse;
use App\User\Dto\Request\SaveNoteRequest;
use App\User\Dto\Response\NoteCollectionResponse;
use App\User\Dto\Response\NoteMutationResponse;
use App\User\Processor\DeleteNoteProcessor;
use App\User\Processor\SaveNoteProcessor;
use App\User\Provider\NoteCollectionProvider;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/notes',
        output: NoteCollectionResponse::class,
        provider: NoteCollectionProvider::class,
    ),
    new Post(
        uriTemplate: '/notes',
        status: 200,
        denormalizationContext: [
            'api_allow_update' => true,
        ],
        input: SaveNoteRequest::class,
        output: NoteMutationResponse::class,
        read: false,
        processor: SaveNoteProcessor::class,
    ),
    new Delete(
        uriTemplate: '/notes/{id}',
        requirements: ['id' => '\\d+'],
        status: 200,
        output: StatusResponse::class,
        read: false,
        processor: DeleteNoteProcessor::class,
    ),
])]
final class NoteDto
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;
}
