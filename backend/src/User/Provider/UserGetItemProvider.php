<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Api\UriVariable;
use App\User\Dto\Response\UserItemResponse;
use App\User\Entity\User;
use App\User\Mapper\UserMapper;
use App\User\Repository\UserRepository;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<UserItemResponse> */
final readonly class UserGetItemProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $repository,
        private UserMapper $userMapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserItemResponse
    {
        $user = $this->repository->findOneBy(['uuid' => UriVariable::uuid($uriVariables)]);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found.');
        }

        return $this->userMapper->toItemResponse($user);
    }
}
