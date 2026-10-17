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
use App\Feed\Dto\Response\ReaderResponse;
use App\Feed\Processor\CreateFeedItemProcessor;
use App\Feed\Processor\FramePolicyProcessor;
use App\Feed\Processor\MarkFeedItemOpenedProcessor;
use App\Feed\Processor\PageMetadataProcessor;
use App\Feed\Processor\ToggleFeedItemPinProcessor;
use App\Feed\Provider\FeedHistoryProvider;
use App\Feed\Provider\FeedItemsProvider;
use App\Feed\Provider\FeedPinnedProvider;
use App\Feed\Provider\FeedOverviewProvider;
use App\Feed\Provider\FeedSearchProvider;
use App\Feed\Provider\FeedTagItemsProvider;
use App\Feed\Provider\ReaderProvider;
use App\Shared\Dto\Response\StatusResponse;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/feed/items',
        output: FeedItemsPageResponse::class,
        provider: FeedItemsProvider::class,
    ),
    new Get(
        uriTemplate: '/feed/pinned',
        output: FeedItemsResponse::class,
        provider: FeedPinnedProvider::class,
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
    new Get(
        uriTemplate: '/feed/reader',
        output: ReaderResponse::class,
        provider: ReaderProvider::class,
    ),
    new Post(
        uriTemplate: '/feed/metadata',
        input: UrlRequest::class,
        output: PageMetadataResponse::class,
        read: false,
        processor: PageMetadataProcessor::class,
        status: 200,
    ),
    new Post(
        uriTemplate: '/feed/items',
        input: CreateFeedItemRequest::class,
        output: FeedItemMutationResponse::class,
        read: false,
        processor: CreateFeedItemProcessor::class,
        status: 201,
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
        input: false,
        output: StatusResponse::class,
        read: false,
        processor: MarkFeedItemOpenedProcessor::class,
        status: 200,
    ),
    new Post(
        uriTemplate: '/feed/frame-policy',
        input: UrlRequest::class,
        output: FramePolicyResponse::class,
        read: false,
        processor: FramePolicyProcessor::class,
        status: 200,
    ),
    new Post(
        uriTemplate: '/feed/import',
        input: CreateFeedItemRequest::class,
        output: FeedItemMutationResponse::class,
        read: false,
        processor: CreateFeedItemProcessor::class,
        status: 201,
    ),
])]
final class FeedDto
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;
}
