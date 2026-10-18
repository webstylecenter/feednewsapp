<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Response\PinFeedItemResponse;
use App\Feed\Mapper\FeedItemMapper;
use App\Feed\Service\FeedService;
use App\Shared\Api\UriVariable;
use App\Shared\Security\CurrentUser;
use Override;

/** @implements ProcessorInterface<mixed, PinFeedItemResponse> */
final readonly class ToggleFeedItemPinProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedService $feedService,
        private FeedItemMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PinFeedItemResponse
    {
        $item = $this->feedService->togglePin(
            $this->currentUser->get(),
            UriVariable::uuid($uriVariables),
        );

        return new PinFeedItemResponse(
            currentState: $item->isPinned(),
            item: $this->mapper->toResponse($item),
        );
    }
}
