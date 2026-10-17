<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Dto\Response\StatusResponse;
use App\Shared\Security\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;
use Override;

/** @implements ProcessorInterface<mixed, StatusResponse> */
final readonly class HideXFrameNoticeProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $this->currentUser->get()->setHideXFrameNotice(true);
        $this->entityManager->flush();
        return new StatusResponse();
    }
}
