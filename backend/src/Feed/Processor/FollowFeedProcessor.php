<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\FollowFeedRequest;
use App\Feed\Dto\Response\SubscriptionMutationResponse;
use App\Feed\Entity\Feed;
use App\Feed\Mapper\FeedSettingsMapper;
use App\Feed\Repository\FeedRepository;
use App\Feed\Service\FeedService;
use App\Shared\Api\UriVariable;
use App\Shared\Security\CurrentUser;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<FollowFeedRequest, SubscriptionMutationResponse> */
final readonly class FollowFeedProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedRepository $feedRepository,
        private FeedService $feedService,
        private FeedSettingsMapper $mapper,
    ) {
    }

    /**
     * @param FollowFeedRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SubscriptionMutationResponse
    {
        $feed = $this->feedRepository->findOneBy(['uuid' => UriVariable::uuid($uriVariables)]);
        if (!$feed instanceof Feed) {
            throw new NotFoundHttpException('Feed not found.');
        }

        $subscription = $this->feedService->followExisting(
            $this->currentUser->get(),
            $feed,
            self::nullableTrimmed($data->color),
            self::nullableTrimmed($data->icon),
            $data->autoPin,
        );

        return new SubscriptionMutationResponse($this->mapper->toSubscriptionResponse($subscription));
    }

    private static function nullableTrimmed(?string $value): ?string
    {
        return $value !== null && trim($value) !== '' ? trim($value) : null;
    }
}
