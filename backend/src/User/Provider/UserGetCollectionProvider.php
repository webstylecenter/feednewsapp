<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\User\Dto\Response\UserResponse;
use App\User\Entity\User;
use App\User\Mapper\UserMapper;
use ArrayIterator;
use RuntimeException;

/**
 * @implements ProviderInterface<UserResponse>
 */
final readonly class UserGetCollectionProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
        private UserMapper $userMapper,
    ) {
    }

    /**
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     * @return array<int, UserResponse>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $entities = $this->collectionProvider->provide($operation, $uriVariables, $context);

        /** @var array<int, UserResponse> $output */
        $output = [];

        foreach ($entities as $user) {
            if (!$user instanceof User) {
                throw new RuntimeException('Entity not of type ' . User::class);
            }

            $output[] = $this->userMapper->toResponse($user);
        }

        if ($entities instanceof Paginator) {
            return iterator_to_array(new TraversablePaginator(
                new ArrayIterator($output),
                $entities->getCurrentPage(),
                $entities->getItemsPerPage(),
                $entities->getTotalItems(),
            ));
        }

        return $output;
    }
}
