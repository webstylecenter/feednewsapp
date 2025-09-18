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
        if (!array_key_exists('id', $uriVariables)) {
            throw new InvalidArgumentException('id is required');
        }

        $rawId = $uriVariables['id'];
        if (is_string($rawId)) {
            if ($rawId === '' || !ctype_digit($rawId)) {
                throw new InvalidArgumentException('id must be a positive integer');
            }
            $uriVariables['id'] = (int) $rawId;
        } elseif (!is_int($rawId) || $rawId < 1) {
            throw new InvalidArgumentException('id must be a positive integer');
        }

        $user = $this->itemProvider->provide($operation, $uriVariables, $context);
        return $user instanceof User
            ? $this->userMapper->toItemResponse($user)
            : throw new NotFoundHttpException('User not found');
    }
}
