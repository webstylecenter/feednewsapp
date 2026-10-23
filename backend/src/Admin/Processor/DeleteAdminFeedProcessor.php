<?php

declare(strict_types=1);

namespace App\Admin\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Admin\Service\AdminFeedRemovalService;
use App\Feed\Entity\Feed;
use App\Feed\Repository\FeedRepository;
use App\Shared\Api\UriVariable;
use App\Shared\Dto\Response\StatusResponse;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<mixed, StatusResponse> */
final readonly class DeleteAdminFeedProcessor implements ProcessorInterface
{
    public function __construct(
        private FeedRepository $repository,
        private AdminFeedRemovalService $removalService,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $feed = $this->repository->findOneBy(['uuid' => UriVariable::uuid($uriVariables)]);
        if (!$feed instanceof Feed) {
            throw new NotFoundHttpException('Feed not found.');
        }

        $this->removalService->remove($feed);

        return new StatusResponse();
    }
}
