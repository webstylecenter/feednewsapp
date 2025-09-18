<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\User\Dto\Response\UserCollectionResponse;
use App\User\Dto\Response\UserItemResponse;
use App\User\Entity\User;
use App\User\Mapper\UserMapper;
use ArrayIterator;
use Doctrine\ORM\EntityNotFoundException;
use InvalidArgumentException;
use RuntimeException;

/**
 * @implements ProviderInterface<UserItemResponse>
 */
final readonly class UserGetItemProvider implements ProviderInterface
{
    public function __construct(
        private ItemProvider $itemProvider,
        private UserMapper $userMapper,
    ) {
    }

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): UserItemResponse {
        if (array_key_exists('id', $uriVariables) === false || !is_int($uriVariables['id'])) {
            throw new InvalidArgumentException('Id must me an integer');
        }

        $user = $this->itemProvider->provide($operation, $uriVariables, $context);
        return $user instanceof User
            ? $this->userMapper->toItemResponse($user)
            : throw new EntityNotFoundException('User not found');
    }
}
