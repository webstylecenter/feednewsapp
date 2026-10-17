<?php

declare(strict_types=1);

namespace App\Admin\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Api\UriVariable;
use App\Shared\Dto\Response\StatusResponse;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use App\User\Service\UserRemovalService;
use Override;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<mixed, StatusResponse> */
final readonly class DeleteAdminUserProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $repository,
        private UserRemovalService $removalService,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $user = $this->repository->find(UriVariable::positiveInt($uriVariables));
        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found.');
        }
        if ($user->isAdmin()) {
            throw new ConflictHttpException('Administrator accounts cannot be removed through this endpoint.');
        }

        $this->removalService->remove($user);
        return new StatusResponse();
    }
}
