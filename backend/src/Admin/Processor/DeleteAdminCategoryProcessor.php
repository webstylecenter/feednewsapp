<?php

declare(strict_types=1);

namespace App\Admin\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Entity\FeedCategory;
use App\Feed\Repository\FeedCategoryRepository;
use App\Shared\Api\UriVariable;
use App\Shared\Dto\Response\StatusResponse;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<mixed, StatusResponse> */
final readonly class DeleteAdminCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private FeedCategoryRepository $repository,
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
        $category = $this->repository->findOneBy(['uuid' => UriVariable::uuid($uriVariables)]);
        if (!$category instanceof FeedCategory) {
            throw new NotFoundHttpException('Feed category not found.');
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return new StatusResponse();
    }
}
