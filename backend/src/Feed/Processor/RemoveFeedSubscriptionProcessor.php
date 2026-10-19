<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Entity\FeedUser;
use App\Feed\Repository\UserFeedRepository;
use App\Feed\Service\FeedSubscriptionRemovalService;
use App\Shared\Api\UriVariable;
use App\Shared\Dto\Response\StatusResponse;
use App\Shared\Security\CurrentUser;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<mixed, StatusResponse> */
final readonly class RemoveFeedSubscriptionProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserFeedRepository $repository,
        private FeedSubscriptionRemovalService $removalService,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $subscription = $this->repository->findOwnedByIdentifier(
            $this->currentUser->get(),
            UriVariable::uuid($uriVariables),
        );
        if (!$subscription instanceof FeedUser) {
            throw new NotFoundHttpException('Feed subscription not found.');
        }

        $this->removalService->remove($subscription);

        return new StatusResponse();
    }
}
