<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\User\Dto\Response\UserCollectionResponse;
use App\User\Entity\User;
use App\User\Mapper\UserMapper;
use App\User\Repository\UserRepository;
use Override;

/**
 * @implements ProviderInterface<UserCollectionResponse>
 */
final readonly class UserGetCollectionProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $repository,
        private UserMapper $userMapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     * @return list<UserCollectionResponse>
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var list<User> $users */
        $users = $this->repository->findBy([], ['createdAt' => 'DESC']);

        return array_map($this->userMapper->toCollectionResponse(...), $users);
    }
}
