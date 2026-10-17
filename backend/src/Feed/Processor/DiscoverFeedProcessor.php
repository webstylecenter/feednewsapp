<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\DiscoverFeedRequest;
use App\Feed\Dto\Response\DiscoverFeedResponse;
use App\Feed\Service\FeedDiscoveryService;
use Override;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<DiscoverFeedRequest, DiscoverFeedResponse> */
final readonly class DiscoverFeedProcessor implements ProcessorInterface
{
    public function __construct(private FeedDiscoveryService $service)
    {
    }

    /**
     * @param DiscoverFeedRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DiscoverFeedResponse
    {
        $websiteUrl = trim($data->websiteUrl) !== '' ? trim($data->websiteUrl) : trim($data->url);
        if (trim($data->feedUrl) === '' && $websiteUrl === '') {
            throw new UnprocessableEntityHttpException('A feed or website URL is required.');
        }

        return new DiscoverFeedResponse(
            $this->service->resolve(trim($data->feedUrl), $websiteUrl),
        );
    }
}
