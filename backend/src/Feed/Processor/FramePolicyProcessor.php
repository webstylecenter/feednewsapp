<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\UrlRequest;
use App\Feed\Dto\Response\FramePolicyResponse;
use App\Feed\Service\FramePolicyService;
use Override;

/** @implements ProcessorInterface<UrlRequest, FramePolicyResponse> */
final readonly class FramePolicyProcessor implements ProcessorInterface
{
    public function __construct(private FramePolicyService $service)
    {
    }

    /**
     * @param UrlRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FramePolicyResponse
    {
        $policy = $this->service->check($data->url);

        return new FramePolicyResponse(
            blocked: $policy['blocked'],
            uncertain: $policy['uncertain'],
        );
    }
}
