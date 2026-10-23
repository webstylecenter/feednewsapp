<?php

declare(strict_types=1);

namespace App\Admin\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Admin\Dto\Request\AdminCategoryRequest;
use App\Feed\Entity\FeedCategory;
use App\Feed\Repository\FeedCategoryRepository;
use App\Shared\Dto\Response\StatusResponse;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** @implements ProcessorInterface<AdminCategoryRequest, StatusResponse> */
final readonly class CreateAdminCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private FeedCategoryRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param AdminCategoryRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $name = trim($data->name);
        if ($this->repository->findOneBy(['name' => $name]) instanceof FeedCategory) {
            throw new ConflictHttpException('Feed category already exists.');
        }

        $this->entityManager->persist(new FeedCategory($name));
        $this->entityManager->flush();

        return new StatusResponse();
    }
}
