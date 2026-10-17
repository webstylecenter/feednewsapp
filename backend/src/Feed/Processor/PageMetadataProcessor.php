<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\UrlRequest;
use App\Feed\Dto\Response\PageMetadataDataResponse;
use App\Feed\Dto\Response\PageMetadataResponse;
use App\Feed\Service\PageMetadataService;
use Override;

/** @implements ProcessorInterface<UrlRequest, PageMetadataResponse> */
final readonly class PageMetadataProcessor implements ProcessorInterface
{
    public function __construct(private PageMetadataService $service)
    {
    }

    /**
     * @param UrlRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PageMetadataResponse
    {
        $metadata = $this->service->fetch($data->url);

        return new PageMetadataResponse(new PageMetadataDataResponse(
            title: $metadata->title,
            description: $metadata->description,
            url: $metadata->url,
        ));
    }
}
