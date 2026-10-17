<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\CreateFeedItemRequest;
use App\Feed\Dto\Response\FeedItemMutationResponse;
use App\Feed\Mapper\FeedItemMapper;
use App\Feed\Service\FeedService;
use App\Shared\Security\CurrentUser;
use Override;

/** @implements ProcessorInterface<CreateFeedItemRequest, FeedItemMutationResponse> */
final readonly class CreateFeedItemProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedService $feedService,
        private FeedItemMapper $mapper,
    ) {
    }

    /**
     * @param CreateFeedItemRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FeedItemMutationResponse
    {
        $item = $this->feedService->createManualItem(
            $this->currentUser->get(),
            $data->url,
            self::nullableTrimmed($data->title),
            self::nullableTrimmed($data->description),
        );

        return new FeedItemMutationResponse($this->mapper->toResponse($item));
    }

    private static function nullableTrimmed(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
