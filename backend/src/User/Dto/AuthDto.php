<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\User\Dto\Request\RegisterRequest;
use App\User\Dto\Response\RegisterResponse;
use App\User\Dto\Response\SessionResponse;
use App\User\Processor\RegisterProcessor;
use App\User\Provider\SessionProvider;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/auth/session',
        output: SessionResponse::class,
        provider: SessionProvider::class,
    ),
    new Post(
        uriTemplate: '/auth/register',
        input: RegisterRequest::class,
        output: RegisterResponse::class,
        read: false,
        processor: RegisterProcessor::class,
        status: 201,
    ),
])]
final class AuthDto
{
}
