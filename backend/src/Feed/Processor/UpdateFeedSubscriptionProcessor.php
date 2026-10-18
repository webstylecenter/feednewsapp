<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\UpdateFeedSubscriptionRequest;
use App\Feed\Dto\Response\SubscriptionMutationResponse;
use App\Feed\Entity\FeedUser;
use App\Feed\Mapper\FeedSettingsMapper;
use App\Feed\Repository\UserFeedRepository;
use App\Feed\Service\FeedService;
use App\Shared\Api\UriVariable;
use App\Shared\Security\CurrentUser;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<UpdateFeedSubscriptionRequest, SubscriptionMutationResponse> */
final readonly class UpdateFeedSubscriptionProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserFeedRepository $repository,
        private FeedService $feedService,
        private FeedSettingsMapper $mapper,
    ) {
    }

    /**
     * @param UpdateFeedSubscriptionRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SubscriptionMutationResponse
    {
        $subscription = $this->repository->findOwnedByFeedUuid(
            $this->currentUser->get(),
            UriVariable::uuid($uriVariables),
        );
        if (!$subscription instanceof FeedUser) {
            throw new NotFoundHttpException('Feed subscription not found.');
        }

        $this->feedService->updateSubscription(
            $subscription,
            $data->color,
            $data->icon,
            $data->autoPin,
        );

        return new SubscriptionMutationResponse($this->mapper->toSubscriptionResponse($subscription));
    }
}
