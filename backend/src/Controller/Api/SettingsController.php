<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedCategory;
use App\Feed\Entity\FeedUser;
use App\Feed\Repository\FeedCategoryRepository;
use App\Feed\Repository\FeedRepository;
use App\Feed\Repository\UserFeedRepository;
use App\Feed\Service\FeedDiscoveryService;
use App\Feed\Service\FeedService;
use App\Shared\Request\RequestData;
use App\Shared\Security\CurrentUser;
use App\User\Entity\UserSetting;
use App\User\Repository\UserSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use LogicException;

#[Route('/api/settings')]
final class SettingsController extends AbstractController
{
    #[Route('', name: 'api_settings_index', methods: ['GET'])]
    public function index(
        CurrentUser $currentUser,
        UserFeedRepository $userFeedRepository,
        FeedRepository $feedRepository,
        FeedCategoryRepository $categoryRepository,
        UserSettingRepository $settingRepository,
    ): JsonResponse {
        $user = $currentUser->get();
        $followedFeedIds = [];
        $subscriptions = [];
        foreach ($userFeedRepository->findForUser($user) as $subscription) {
            $feed = $subscription->getFeed();
            $feedId = $feed->getId();
            $subscriptionId = $subscription->getId();
            if ($feedId === null || $subscriptionId === null) {
                continue;
            }

            $followedFeedIds[$feedId] = true;
            $subscriptions[] = self::presentSubscription($subscription);
        }

        $availableFeeds = [];
        foreach ($feedRepository->findAllOrdered() as $feed) {
            $id = $feed->getId();
            if ($id === null || isset($followedFeedIds[$id])) {
                continue;
            }
            $availableFeeds[] = self::presentFeed($feed);
        }

        $categories = [];
        foreach ($categoryRepository->findAllOrdered() as $category) {
            $id = $category->getId();
            if ($id !== null) {
                $categories[] = ['id' => $id, 'name' => $category->getName()];
            }
        }

        return $this->json([
            'subscriptions' => $subscriptions,
            'availableFeeds' => $availableFeeds,
            'categories' => $categories,
            'preferences' => $settingRepository->valuesForUser($user),
            'hideXFrameNotice' => $user->isHideXFrameNotice(),
        ]);
    }

    #[Route('/feeds', name: 'api_settings_add_feed', methods: ['POST'])]
    public function addFeed(
        Request $request,
        CurrentUser $currentUser,
        FeedCategoryRepository $categoryRepository,
        FeedService $feedService,
    ): JsonResponse {
        $data = RequestData::all($request);
        $categoryId = RequestData::int($data, 'category');
        $category = null;
        if ($categoryId !== null) {
            $category = $categoryRepository->find($categoryId);
            if (!$category instanceof FeedCategory) {
                throw $this->createNotFoundException('Feed category not found.');
            }
        }

        $feedUrl = RequestData::string($data, 'feedUrl');
        $websiteUrl = RequestData::string($data, 'websiteUrl');
        if ($feedUrl === '' && $websiteUrl === '') {
            $websiteUrl = RequestData::string($data, 'url');
        }

        if ($feedUrl === '' && $websiteUrl === '') {
            return $this->json(
                ['status' => 'error', 'message' => 'A feed or website URL is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $subscription = $feedService->subscribe(
            $currentUser->get(),
            $feedUrl,
            $websiteUrl,
            $category,
            RequestData::string($data, 'color') ?: null,
            RequestData::string($data, 'icon') ?: null,
            RequestData::bool($data, 'autoPin'),
        );

        return $this->json(
            ['status' => 'success', 'subscription' => self::presentSubscription($subscription)],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/feeds/{id<\d+>}/follow', name: 'api_settings_follow_feed', methods: ['POST'])]
    public function followFeed(
        int $id,
        Request $request,
        CurrentUser $currentUser,
        FeedRepository $feedRepository,
        FeedService $feedService,
    ): JsonResponse {
        $feed = $feedRepository->find($id);
        if (!$feed instanceof Feed) {
            throw $this->createNotFoundException('Feed not found.');
        }

        $data = RequestData::all($request);
        $subscription = $feedService->followExisting(
            $currentUser->get(),
            $feed,
            RequestData::string($data, 'color') ?: null,
            RequestData::string($data, 'icon') ?: null,
            RequestData::bool($data, 'autoPin'),
        );

        return $this->json(
            ['status' => 'success', 'subscription' => self::presentSubscription($subscription)],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/feeds/{id<\d+>}', name: 'api_settings_update_feed', methods: ['PATCH'])]
    public function updateFeed(
        int $id,
        Request $request,
        CurrentUser $currentUser,
        UserFeedRepository $repository,
        FeedService $feedService,
    ): JsonResponse {
        $subscription = $repository->findOwnedById($currentUser->get(), $id);
        if (!$subscription instanceof FeedUser) {
            throw $this->createNotFoundException('Feed subscription not found.');
        }

        $data = RequestData::all($request);
        $feedService->updateSubscription(
            $subscription,
            array_key_exists('color', $data) ? RequestData::string($data, 'color') : null,
            array_key_exists('icon', $data) ? RequestData::string($data, 'icon') : null,
            array_key_exists('autoPin', $data) ? RequestData::bool($data, 'autoPin') : null,
        );

        return $this->json(['status' => 'success', 'subscription' => self::presentSubscription($subscription)]);
    }

    #[Route('/feeds/{id<\d+>}', name: 'api_settings_remove_feed', methods: ['DELETE'])]
    public function removeFeed(
        int $id,
        CurrentUser $currentUser,
        UserFeedRepository $repository,
        FeedService $feedService,
    ): JsonResponse {
        $subscription = $repository->findOwnedById($currentUser->get(), $id);
        if (!$subscription instanceof FeedUser) {
            throw $this->createNotFoundException('Feed subscription not found.');
        }

        $feedService->removeSubscription($subscription);
        return $this->json(['status' => 'success']);
    }

    #[Route('/discover', name: 'api_settings_discover_feed', methods: ['POST'])]
    public function discover(Request $request, FeedDiscoveryService $service): JsonResponse
    {
        $data = RequestData::all($request);
        $feedUrl = RequestData::string($data, 'feedUrl');
        $websiteUrl = RequestData::string($data, 'websiteUrl');
        if ($feedUrl === '' && $websiteUrl === '') {
            $websiteUrl = RequestData::string($data, 'url');
        }

        return $this->json(['status' => 'success', 'feedUrl' => $service->resolve($feedUrl, $websiteUrl)]);
    }

    #[Route('/preferences/{setting}', name: 'api_settings_preference', requirements: ['setting' => '[a-zA-Z0-9_.-]+'], methods: ['PUT'])]
    public function preference(
        string $setting,
        Request $request,
        CurrentUser $currentUser,
        UserSettingRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!in_array($setting, ['theme', 'google_calendar'], true)) {
            throw $this->createNotFoundException('Unknown preference.');
        }

        $value = RequestData::string(RequestData::all($request), 'value');
        if ($setting === 'theme' && !in_array($value, ['auto', 'light', 'dark'], true)) {
            return $this->json(
                ['status' => 'error', 'message' => 'Theme must be auto, light or dark.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
        if ($setting === 'google_calendar' && $value !== '' && self::isValidGoogleCalendarUrl($value) === false) {
            return $this->json(
                ['status' => 'error', 'message' => 'Google Calendar must use an HTTPS calendar.google.com URL.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = $currentUser->get();
        $preference = $repository->findForUserAndKey($user, $setting);
        if (!$preference instanceof UserSetting) {
            $preference = new UserSetting($user, mb_substr($setting, 0, 255), $value !== '' ? $value : null);
            $entityManager->persist($preference);
        } else {
            $preference->setValue($value !== '' ? $value : null);
        }
        $entityManager->flush();

        return $this->json(['status' => 'success']);
    }

    #[Route('/hide-x-frame-notice', name: 'api_settings_hide_xframe', methods: ['POST'])]
    public function hideXFrameNotice(CurrentUser $currentUser, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUser->get()->setHideXFrameNotice(true);
        $entityManager->flush();

        return $this->json(['status' => 'success']);
    }

    /** @return array<string, mixed> */
    private static function presentSubscription(FeedUser $subscription): array
    {
        $id = $subscription->getId();
        if ($id === null) {
            throw new LogicException('Cannot present an unpersisted subscription.');
        }

        return [
            'id' => $id,
            'feed' => self::presentFeed($subscription->getFeed()),
            'color' => $subscription->getColor() ?? $subscription->getFeed()->getColor(),
            'icon' => $subscription->getIcon() ?? '',
            'autoPin' => $subscription->isAutoPin(),
        ];
    }

    /** @return array{id: int, name: string, url: string, color: string, category: string|null} */
    private static function presentFeed(Feed $feed): array
    {
        $id = $feed->getId();
        if ($id === null) {
            throw new LogicException('Cannot present an unpersisted feed.');
        }

        return [
            'id' => $id,
            'name' => $feed->getName(),
            'url' => $feed->getUrl(),
            'color' => $feed->getColor(),
            'category' => $feed->getCategory()?->getName(),
        ];
    }

    private static function isValidGoogleCalendarUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $scheme === 'https' && ($host === 'calendar.google.com' || str_ends_with($host, '.calendar.google.com'));
    }
}
