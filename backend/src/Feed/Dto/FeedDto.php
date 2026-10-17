<?php

declare(strict_types=1);

namespace App\Feed\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Feed\Dto\Request\CreateFeedItemRequest;
use App\Feed\Dto\Request\UrlRequest;
use App\Feed\Dto\Response\FeedItemMutationResponse;
use App\Feed\Dto\Response\FeedItemsPageResponse;
use App\Feed\Dto\Response\FeedItemsResponse;
use App\Feed\Dto\Response\FeedOverviewResponse;
use App\Feed\Dto\Response\FramePolicyResponse;
use App\Feed\Dto\Response\PageMetadataResponse;
use App\Feed\Dto\Response\PinFeedItemResponse;
use App\Feed\Processor\CreateFeedItemProcessor;
use App\Feed\Processor\FramePolicyProcessor;
use App\Feed\Processor\MarkFeedItemOpenedProcessor;
use App\Feed\Processor\PageMetadataProcessor;
use App\Feed\Processor\ToggleFeedItemPinProcessor;
use App\Feed\Provider\FeedHistoryProvider;
use App\Feed\Provider\FeedItemsProvider;
use App\Feed\Provider\FeedOverviewProvider;
use App\Feed\Provider\FeedSearchProvider;
use App\Feed\Provider\FeedTagItemsProvider;
use App\Shared\Dto\Response\StatusResponse;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/feed/items',
        output: FeedItemsPageResponse::class,
        provider: FeedItemsProvider::class,
    ),
    new Get(
        uriTemplate: '/feed/history',
        output: FeedItemsResponse::class,
        provider: FeedHistoryProvider::class,
    ),
    new Get(
        uriTemplate: '/feed/search',
        output: FeedItemsResponse::class,
        provider: FeedSearchProvider::class,
    ),
    new Get(
        uriTemplate: '/feed/tag/{id}',
        requirements: ['id' => '\\d+'],
        output: FeedItemsResponse::class,
        provider: FeedTagItemsProvider::class,
    ),
    new Get(
        uriTemplate: '/feed/overview',
        output: FeedOverviewResponse::class,
        provider: FeedOverviewProvider::class,
    ),
    new Post(
        uriTemplate: '/feed/metadata',
        status: 200,
        input: UrlRequest::class,
        output: PageMetadataResponse::class,
        read: false,
        processor: PageMetadataProcessor::class,
    ),
    new Post(
        uriTemplate: '/feed/items',
        status: 201,
        input: CreateFeedItemRequest::class,
        output: FeedItemMutationResponse::class,
        read: false,
        processor: CreateFeedItemProcessor::class,
    ),
    new Patch(
        uriTemplate: '/feed/items/{id}/pin',
        requirements: ['id' => '\\d+'],
        input: false,
        output: PinFeedItemResponse::class,
        read: false,
        processor: ToggleFeedItemPinProcessor::class,
    ),
    new Post(
        uriTemplate: '/feed/items/{id}/opened',
        requirements: ['id' => '\\d+'],
        status: 200,
        input: false,
        output: StatusResponse::class,
        read: false,
        processor: MarkFeedItemOpenedProcessor::class,
    ),
    new Post(
        uriTemplate: '/feed/frame-policy',
        status: 200,
        input: UrlRequest::class,
        output: FramePolicyResponse::class,
        read: false,
        processor: FramePolicyProcessor::class,
    ),
    new Post(
        uriTemplate: '/feed/import',
        status: 201,
        input: CreateFeedItemRequest::class,
        output: FeedItemMutationResponse::class,
        read: false,
        processor: CreateFeedItemProcessor::class,
    ),
])]
final class FeedDto
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;
}
