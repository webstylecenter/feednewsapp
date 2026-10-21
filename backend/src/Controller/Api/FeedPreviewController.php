<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedItem;
use App\Feed\Repository\FeedItemRepository;
use App\Feed\Repository\FeedRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class FeedPreviewController
{
    public function __construct(
        private FeedRepository $feedRepository,
        private FeedItemRepository $feedItemRepository,
    ) {
    }

    #[Route('/api/settings/feeds/{uuid}/preview', name: 'api_settings_feed_preview', methods: ['GET'])]
    public function __invoke(string $uuid): JsonResponse
    {
        $feed = $this->feedRepository->findOneBy(['uuid' => $uuid]);
        if (!$feed instanceof Feed) {
            throw new NotFoundHttpException('Feed not found.');
        }

        return new JsonResponse([
            'uuid' => $feed->getUuid()->toRfc4122(),
            'name' => $feed->getName(),
            'items' => array_map(
                static fn (FeedItem $item): array => [
                    'uuid' => $item->getUuid()->toRfc4122(),
                    'title' => $item->getTitle(),
                    'description' => $item->getDescription(),
                    'url' => $item->getUrl(),
                    'createdAt' => $item->getCreatedAt()?->format(DATE_ATOM),
                ],
                $this->feedItemRepository->findLatestForFeed($feed, 4),
            ),
        ]);
    }
}
