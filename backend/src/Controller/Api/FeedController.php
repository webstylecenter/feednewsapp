<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Feed\Entity\FeedTag;
use App\Feed\Entity\FeedUserItem;
use App\Feed\Repository\FeedTagRepository;
use App\Feed\Repository\UserFeedItemRepository;
use App\Feed\Repository\UserFeedRepository;
use App\Feed\Service\FeedItemPresenter;
use App\Feed\Service\FeedService;
use App\Feed\Service\FramePolicyService;
use App\Feed\Service\PageMetadataService;
use App\Shared\Request\RequestData;
use App\Shared\Security\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/feed')]
final class FeedController extends AbstractController
{
    #[Route('/items', name: 'api_feed_items', methods: ['GET'])]
    public function items(
        Request $request,
        CurrentUser $currentUser,
        FeedService $feedService,
        FeedItemPresenter $presenter,
    ): JsonResponse {
        $page = max(0, $request->query->getInt('page'));
        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $items = $feedService->recent($currentUser->get(), $page, $limit);

        return $this->json([
            'items' => $presenter->presentMany($items),
            'nextPage' => count($items) === $limit ? $page + 1 : null,
        ]);
    }

    #[Route('/history', name: 'api_feed_history', methods: ['GET'])]
    public function history(
        CurrentUser $currentUser,
        UserFeedItemRepository $repository,
        FeedItemPresenter $presenter,
    ): JsonResponse {
        return $this->json([
            'items' => $presenter->presentMany($repository->findOpenedForUser($currentUser->get())),
        ]);
    }

    #[Route('/search', name: 'api_feed_search', methods: ['GET'])]
    public function search(
        Request $request,
        CurrentUser $currentUser,
        UserFeedItemRepository $repository,
        FeedItemPresenter $presenter,
    ): JsonResponse {
        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) < 2) {
            return $this->json(
                ['status' => 'error', 'message' => 'Search query must contain at least 2 characters.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->json([
            'items' => $presenter->presentMany($repository->searchForUser($currentUser->get(), $query)),
        ]);
    }

    #[Route('/tag/{id<\d+>}', name: 'api_feed_tag', methods: ['GET'])]
    public function byTag(
        int $id,
        CurrentUser $currentUser,
        FeedTagRepository $tagRepository,
        UserFeedItemRepository $itemRepository,
        FeedItemPresenter $presenter,
    ): JsonResponse {
        $user = $currentUser->get();
        $tag = $tagRepository->findOwnedById($user, $id);
        if (!$tag instanceof FeedTag) {
            throw $this->createNotFoundException('Tag not found.');
        }

        return $this->json([
            'items' => $presenter->presentMany($itemRepository->findByTagForUser($user, $tag)),
        ]);
    }

    #[Route('/overview', name: 'api_feed_overview', methods: ['GET'])]
    public function overview(CurrentUser $currentUser, UserFeedRepository $repository): JsonResponse
    {
        $items = [];
        foreach ($repository->findForUser($currentUser->get()) as $subscription) {
            $feed = $subscription->getFeed();
            $id = $subscription->getId();
            if ($id === null) {
                continue;
            }

            $items[] = [
                'id' => $id,
                'name' => $feed->getName(),
                'color' => $subscription->getColor() ?? $feed->getColor(),
                'icon' => $subscription->getIcon() ?? '',
                'autoPin' => $subscription->isAutoPin(),
                'itemCount' => $subscription->getItems()->count(),
            ];
        }

        return $this->json(['items' => $items]);
    }

    #[Route('/metadata', name: 'api_feed_metadata', methods: ['POST'])]
    public function metadata(Request $request, PageMetadataService $metadataService): JsonResponse
    {
        $url = RequestData::string(RequestData::all($request), 'url');
        if ($url === '') {
            return $this->json(
                ['status' => 'error', 'message' => 'URL is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $metadata = $metadataService->fetch($url);

        return $this->json([
            'status' => 'success',
            'data' => [
                'title' => $metadata->title,
                'description' => $metadata->description,
                'url' => $metadata->url,
            ],
        ]);
    }

    #[Route('/items', name: 'api_feed_create_item', methods: ['POST'])]
    public function createItem(
        Request $request,
        CurrentUser $currentUser,
        FeedService $feedService,
        FeedItemPresenter $presenter,
    ): JsonResponse {
        $data = RequestData::all($request);
        $url = RequestData::string($data, 'url');
        if ($url === '') {
            return $this->json(
                ['status' => 'error', 'message' => 'URL is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $item = $feedService->createManualItem(
            $currentUser->get(),
            $url,
            RequestData::string($data, 'title') ?: null,
            RequestData::string($data, 'description') ?: null,
        );

        return $this->json(
            ['status' => 'success', 'item' => $presenter->present($item)],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/items/{id<\d+>}/pin', name: 'api_feed_pin', methods: ['PATCH'])]
    public function pin(
        int $id,
        CurrentUser $currentUser,
        FeedService $feedService,
        FeedItemPresenter $presenter,
    ): JsonResponse {
        $item = $feedService->togglePin($currentUser->get(), $id);

        return $this->json([
            'status' => 'success',
            'currentState' => $item->isPinned(),
            'item' => $presenter->present($item),
        ]);
    }

    #[Route('/items/{id<\d+>}/opened', name: 'api_feed_opened', methods: ['POST'])]
    public function opened(int $id, CurrentUser $currentUser, FeedService $feedService): JsonResponse
    {
        $feedService->markOpened($currentUser->get(), $id);

        return $this->json(['status' => 'success']);
    }

    #[Route('/frame-policy', name: 'api_feed_frame_policy', methods: ['POST'])]
    public function framePolicy(Request $request, FramePolicyService $framePolicyService): JsonResponse
    {
        $url = RequestData::string(RequestData::all($request), 'url');
        if ($url === '') {
            return $this->json(
                ['status' => 'error', 'message' => 'URL is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->json($framePolicyService->check($url));
    }

    #[Route('/import', name: 'api_feed_import', methods: ['POST'])]
    public function import(
        Request $request,
        CurrentUser $currentUser,
        FeedService $feedService,
        FeedItemPresenter $presenter,
    ): JsonResponse {
        return $this->createItem($request, $currentUser, $feedService, $presenter);
    }
}
