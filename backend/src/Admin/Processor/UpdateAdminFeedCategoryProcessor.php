<?php

declare(strict_types=1);

namespace App\Admin\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Admin\Dto\Request\AdminFeedCategoryRequest;
use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedCategory;
use App\Feed\Repository\FeedCategoryRepository;
use App\Feed\Repository\FeedRepository;
use App\Shared\Api\UriVariable;
use App\Shared\Dto\Response\StatusResponse;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<AdminFeedCategoryRequest, StatusResponse> */
final readonly class UpdateAdminFeedCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private FeedRepository $feedRepository,
        private FeedCategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param AdminFeedCategoryRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $feed = $this->feedRepository->findOneBy(['uuid' => UriVariable::uuid($uriVariables)]);
        if (!$feed instanceof Feed) {
            throw new NotFoundHttpException('Feed not found.');
        }

        $category = null;
        if ($data->category !== null) {
            $category = $this->categoryRepository->findOneBy(['uuid' => $data->category]);
            if (!$category instanceof FeedCategory) {
                throw new NotFoundHttpException('Feed category not found.');
            }
        }

        $feed->setCategory($category);
        $this->entityManager->flush();

        return new StatusResponse();
    }
}
