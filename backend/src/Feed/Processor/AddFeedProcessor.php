<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\AddFeedRequest;
use App\Feed\Dto\Response\SubscriptionMutationResponse;
use App\Feed\Entity\FeedCategory;
use App\Feed\Mapper\FeedSettingsMapper;
use App\Feed\Repository\FeedCategoryRepository;
use App\Feed\Service\FeedService;
use App\Shared\Security\CurrentUser;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<AddFeedRequest, SubscriptionMutationResponse> */
final readonly class AddFeedProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedCategoryRepository $categoryRepository,
        private FeedService $feedService,
        private FeedSettingsMapper $mapper,
    ) {
    }

    /**
     * @param AddFeedRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SubscriptionMutationResponse
    {
        $category = null;
        if ($data->category !== null) {
            $category = $this->categoryRepository->find($data->category);
            if (!$category instanceof FeedCategory) {
                throw new NotFoundHttpException('Feed category not found.');
            }
        }

        $websiteUrl = trim($data->websiteUrl) !== '' ? trim($data->websiteUrl) : trim($data->url);
        if (trim($data->feedUrl) === '' && $websiteUrl === '') {
            throw new UnprocessableEntityHttpException('A feed or website URL is required.');
        }

        $subscription = $this->feedService->subscribe(
            $this->currentUser->get(),
            trim($data->feedUrl),
            $websiteUrl,
            $category,
            self::nullableTrimmed($data->color),
            self::nullableTrimmed($data->icon),
            $data->autoPin,
        );

        return new SubscriptionMutationResponse($this->mapper->toSubscriptionResponse($subscription));
    }

    private static function nullableTrimmed(?string $value): ?string
    {
        return $value !== null && trim($value) !== '' ? trim($value) : null;
    }
}
