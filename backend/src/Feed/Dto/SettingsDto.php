<?php

declare(strict_types=1);

namespace App\Feed\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Feed\Dto\Request\AddFeedRequest;
use App\Feed\Dto\Request\DiscoverFeedRequest;
use App\Feed\Dto\Request\FollowFeedRequest;
use App\Feed\Dto\Request\UpdateFeedSubscriptionRequest;
use App\Feed\Dto\Response\DiscoverFeedResponse;
use App\Feed\Dto\Response\SettingsResponse;
use App\Feed\Dto\Response\SubscriptionMutationResponse;
use App\Feed\Processor\AddFeedProcessor;
use App\Feed\Processor\DiscoverFeedProcessor;
use App\Feed\Processor\FollowFeedProcessor;
use App\Feed\Processor\HideXFrameNoticeProcessor;
use App\Feed\Processor\RemoveFeedSubscriptionProcessor;
use App\Feed\Processor\UpdateFeedSubscriptionProcessor;
use App\Feed\Provider\SettingsProvider;
use App\Shared\Dto\Response\StatusResponse;

#[ApiResource(operations: [
    new Get(uriTemplate: '/settings', output: SettingsResponse::class, provider: SettingsProvider::class),
    new Post(
        uriTemplate: '/settings/feeds',
        input: AddFeedRequest::class,
        output: SubscriptionMutationResponse::class,
        read: false,
        processor: AddFeedProcessor::class,
        status: 201,
    ),
    new Post(
        uriTemplate: '/settings/feeds/{uuid}/follow',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        input: FollowFeedRequest::class,
        output: SubscriptionMutationResponse::class,
        read: false,
        processor: FollowFeedProcessor::class,
        status: 201,
    ),
    new Patch(
        uriTemplate: '/settings/feeds/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        input: UpdateFeedSubscriptionRequest::class,
        output: SubscriptionMutationResponse::class,
        read: false,
        processor: UpdateFeedSubscriptionProcessor::class,
    ),
    new Delete(
        uriTemplate: '/settings/feeds/{uuid}',
        requirements: ['uuid' => '[0-9a-fA-F-]{36}'],
        output: StatusResponse::class,
        read: false,
        processor: RemoveFeedSubscriptionProcessor::class,
        status: 200,
    ),
    new Post(
        uriTemplate: '/settings/discover',
        input: DiscoverFeedRequest::class,
        output: DiscoverFeedResponse::class,
        read: false,
        processor: DiscoverFeedProcessor::class,
        status: 200,
    ),
    new Post(
        uriTemplate: '/settings/hide-x-frame-notice',
        input: false,
        output: StatusResponse::class,
        read: false,
        processor: HideXFrameNoticeProcessor::class,
        status: 200,
    ),
])]
final class SettingsDto
{
    #[ApiProperty(identifier: true)]
    public ?string $uuid = null;
}
