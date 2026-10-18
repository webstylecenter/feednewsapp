<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Shared\Dto\Response\StatusResponse;
use App\User\Dto\Request\SaveNoteRequest;
use App\User\Dto\Response\NoteCollectionResponse;
use App\User\Dto\Response\NoteMutationResponse;
use App\User\Processor\DeleteNoteProcessor;
use App\User\Processor\SaveNoteProcessor;
use App\User\Provider\NoteCollectionProvider;

#[ApiResource(operations: [
    new Get(uriTemplate: '/notes', output: NoteCollectionResponse::class, provider: NoteCollectionProvider::class),
    new Post(
        uriTemplate: '/notes',
        input: SaveNoteRequest::class,
        output: NoteMutationResponse::class,
        read: false,
        processor: SaveNoteProcessor::class,
        status: 200,
    ),
    new Patch(
        uriTemplate: '/notes/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        input: SaveNoteRequest::class,
        output: NoteMutationResponse::class,
        read: false,
        processor: SaveNoteProcessor::class,
        status: 200,
    ),
    new Delete(
        uriTemplate: '/notes/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        output: StatusResponse::class,
        read: false,
        processor: DeleteNoteProcessor::class,
        status: 200,
    ),
])]
final class NoteDto
{
    #[ApiProperty(identifier: true)]
    public ?string $uuid = null;
}
