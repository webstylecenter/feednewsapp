<?php

declare(strict_types=1);

namespace App\User\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use App\Shared\Dto\Response\StatusResponse;
use App\User\Dto\Request\PreferenceRequest;
use App\User\Processor\UpdatePreferenceProcessor;

#[ApiResource(operations: [
    new Put(
        uriTemplate: '/settings/preferences/{setting}',
        requirements: ['setting' => '[a-zA-Z0-9_.-]+'],
        input: PreferenceRequest::class,
        output: StatusResponse::class,
        read: false,
        processor: UpdatePreferenceProcessor::class,
    ),
])]
final class PreferenceDto
{
    #[ApiProperty(identifier: true)]
    public ?string $setting = null;
}
