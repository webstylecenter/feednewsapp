<?php

declare(strict_types=1);

namespace App\User\Processor;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Dto\Request\UserRequest;
use App\User\Dto\Response\UserItemResponse;
use App\User\Mapper\UserMapper;
use Symfony\Component\HttpFoundation\Request;

/**
 * @implements ProcessorInterface<UserRequest, UserItemResponse>
 */
final readonly class UserPostProcessor implements ProcessorInterface
{
    public function __construct(
        private PersistProcessor $persistProcessor,
        private UserMapper $userMapper,
    ) {
    }

    /**
     * @param UserRequest $data
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed>&array{request?: Request, previous_data?: mixed, resource_class?: string|null, original_data?: mixed} $context
     * @return UserItemResponse
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): UserItemResponse {
        return $this->userMapper->toItemResponse(
            $this->persistProcessor->process(
                data: $this->userMapper->toEntity($data),
                operation: $operation,
                uriVariables: $uriVariables,
                context: $context,
            )
        );
    }
}
