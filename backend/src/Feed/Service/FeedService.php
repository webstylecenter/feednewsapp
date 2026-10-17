<?php

declare(strict_types=1);

namespace App\Feed\Service;

use App\Feed\Dto\ParsedFeed;
use App\Feed\Dto\ParsedFeedItem;
use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedCategory;
use App\Feed\Entity\FeedItem;
use App\Feed\Entity\FeedUser;
use App\Feed\Entity\FeedUserItem;
use App\Feed\Repository\FeedItemRepository;
use App\Feed\Repository\FeedRepository;
use App\Feed\Repository\UserFeedItemRepository;
use App\Feed\Repository\UserFeedRepository;
use App\Shared\Http\NativeHttpClient;
use App\Shared\Http\PublicUrlGuard;
use App\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

final readonly class FeedService
{
    public function __construct(
        private NativeHttpClient $httpClient,
        private PublicUrlGuard $urlGuard,
        private FeedParser $feedParser,
        private FeedDiscoveryService $feedDiscoveryService,
        private PageMetadataService $pageMetadataService,
        private FeedRepository $feedRepository,
        private FeedItemRepository $feedItemRepository,
        private UserFeedRepository $userFeedRepository,
        private UserFeedItemRepository $userFeedItemRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function subscribe(
        User $user,
        string $feedUrl,
        string $websiteUrl,
        ?FeedCategory $category,
        ?string $color,
        ?string $icon,
        bool $autoPin,
    ): FeedUser {
        $url = $this->feedDiscoveryService->resolve($feedUrl, $websiteUrl);
        $existingFeed = $this->feedRepository->findOneBy(['url' => $url]);
        if ($existingFeed instanceof Feed) {
            return $this->followExisting($user, $existingFeed, $color, $icon, $autoPin);
        }

        $parsedFeed = $this->downloadAndParse($url);
        $feed = new Feed(
            $category,
            $parsedFeed->title,
            $this->normalizeColor($color ?? '#337dff'),
            $url,
        );
        $subscription = new FeedUser($user, $feed, $autoPin, $icon, $this->normalizeNullableColor($color));
        $user->addUserFeed($subscription);

        $this->entityManager->persist($feed);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->persistParsedItems($feed, $parsedFeed);
        $this->entityManager->flush();

        return $subscription;
    }

    public function followExisting(
        User $user,
        Feed $feed,
        ?string $color = null,
        ?string $icon = null,
        bool $autoPin = false,
    ): FeedUser {
        if ($this->userFeedRepository->isFollowing($user, $feed)) {
            throw new RuntimeException('This feed is already added to your account.');
        }

        $subscription = new FeedUser(
            $user,
            $feed,
            $autoPin,
            $icon,
            $this->normalizeNullableColor($color),
        );
        $user->addUserFeed($subscription);
        $this->entityManager->persist($subscription);

        foreach ($this->feedItemRepository->findLatestForFeed($feed, 25) as $feedItem) {
            $this->createUserItem($user, $subscription, $feedItem, $autoPin);
        }

        $this->entityManager->flush();
        return $subscription;
    }

    public function updateSubscription(
        FeedUser $subscription,
        ?string $color,
        ?string $icon,
        ?bool $autoPin,
    ): void {
        if ($color !== null) {
            $subscription->setColor($this->normalizeNullableColor($color));
        }
        if ($icon !== null) {
            $subscription->setIcon(trim($icon) !== '' ? mb_substr(trim($icon), 0, 64) : null);
        }
        if ($autoPin !== null) {
            $subscription->setAutoPin($autoPin);
        }

        $this->entityManager->flush();
    }

    public function removeSubscription(FeedUser $subscription): void
    {
        $this->entityManager->remove($subscription);
        $this->entityManager->flush();
    }

    public function parseFeed(Feed $feed): int
    {
        $parsedFeed = $this->downloadAndParse($feed->getUrl());
        if ($feed->getName() === '') {
            $feed->setName($parsedFeed->title);
        }

        $newItems = $this->persistParsedItems($feed, $parsedFeed);
        $feed->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $newItems;
    }

    public function createManualItem(
        User $user,
        string $url,
        ?string $title = null,
        ?string $description = null,
    ): FeedUserItem {
        $safeUrl = $this->urlGuard->normalize($url);
        $metadata = $title === null || trim($title) === ''
            ? $this->pageMetadataService->fetch($safeUrl)
            : null;

        $resolvedUrl = $metadata?->url ?? $safeUrl;
        $resolvedTitle = $metadata?->title ?? trim((string) $title);
        $resolvedDescription = $metadata?->description ?? trim((string) $description);
        if ($resolvedTitle === '') {
            $resolvedTitle = $resolvedUrl;
        }

        $feedItem = new FeedItem(
            mb_substr($resolvedTitle, 0, 255),
            $resolvedDescription !== '' ? mb_substr($resolvedDescription, 0, 500) : null,
            null,
            hash('sha256', $user->getUuid()->toRfc4122() . $resolvedUrl . random_bytes(16)),
            $resolvedUrl,
        );
        $userItem = new FeedUserItem($user, $feedItem, null, null);
        $userItem->setPinned(true);

        $this->entityManager->persist($feedItem);
        $this->entityManager->persist($userItem);
        $this->entityManager->flush();

        return $userItem;
    }

    /** @return list<FeedUserItem> */
    public function recent(User $user, int $page, int $limit = 50): array
    {
        $page = max(0, $page);
        $limit = max(1, min(100, $limit));
        $items = $this->userFeedItemRepository->findRecentForUser($user, $limit, $page * $limit);

        $changed = false;
        foreach ($items as $item) {
            if ($item->isViewed() === false) {
                $item->setViewed(true);
                $changed = true;
            }
        }
        if ($changed) {
            $this->entityManager->flush();
        }

        return $items;
    }

    public function togglePin(User $user, int $id): FeedUserItem
    {
        $item = $this->userFeedItemRepository->findOwnedById($user, $id);
        if (!$item instanceof FeedUserItem) {
            throw new RuntimeException('Feed item not found.');
        }

        $item->setPinned(!$item->isPinned());
        $this->entityManager->flush();
        return $item;
    }

    public function markOpened(User $user, int $id): void
    {
        $item = $this->userFeedItemRepository->findOwnedById($user, $id);
        if (!$item instanceof FeedUserItem) {
            throw new RuntimeException('Feed item not found.');
        }

        $item->setOpenedAt(new DateTimeImmutable());
        $this->entityManager->flush();
    }

    private function downloadAndParse(string $url): ParsedFeed
    {
        $response = $this->httpClient->get($url, [
            'Accept' => 'application/rss+xml,application/atom+xml,application/xml,text/xml;q=0.9,*/*;q=0.1',
        ]);
        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new RuntimeException(sprintf('Feed returned HTTP %d.', $response->statusCode));
        }

        return $this->feedParser->parse($response->body);
    }

    private function persistParsedItems(Feed $feed, ParsedFeed $parsedFeed): int
    {
        $newItems = 0;
        foreach ($parsedFeed->items as $parsedItem) {
            $guid = $this->normalizeGuid($parsedItem->guid);
            if ($this->feedItemRepository->findOneBy(['feed' => $feed, 'guid' => $guid]) instanceof FeedItem) {
                continue;
            }

            try {
                $safeUrl = $this->urlGuard->normalize($parsedItem->url);
            } catch (InvalidArgumentException) {
                continue;
            }

            $feedItem = $this->createFeedItem($feed, $parsedItem, $guid, $safeUrl);
            $this->entityManager->persist($feedItem);
            foreach ($this->userFeedRepository->findForFeed($feed) as $subscription) {
                $this->createUserItem(
                    $subscription->getUser(),
                    $subscription,
                    $feedItem,
                    $subscription->isAutoPin(),
                );
            }
            ++$newItems;
        }

        return $newItems;
    }

    private function createFeedItem(
        Feed $feed,
        ParsedFeedItem $item,
        string $guid,
        string $url,
    ): FeedItem {
        $feedItem = new FeedItem($item->title, $item->description, $feed, $guid, $url);
        if ($item->publishedAt instanceof DateTimeImmutable) {
            $feedItem->setCreatedAt($item->publishedAt);
        }
        return $feedItem;
    }

    private function createUserItem(User $user, FeedUser $subscription, FeedItem $feedItem, bool $pinned): void
    {
        if ($this->userFeedItemRepository->findOneBy(['user' => $user, 'feedItem' => $feedItem]) instanceof FeedUserItem) {
            return;
        }

        $item = new FeedUserItem($user, $feedItem, $subscription, null);
        $item->setPinned($pinned);
        $subscription->addUserFeedItem($item);
        $this->entityManager->persist($item);
    }

    private function normalizeGuid(string $guid): string
    {
        $guid = trim($guid);
        if (mb_strlen($guid) <= 255) {
            return $guid;
        }

        return hash('sha256', $guid);
    }

    private function normalizeNullableColor(?string $color): ?string
    {
        if ($color === null || trim($color) === '') {
            return null;
        }

        return $this->normalizeColor($color);
    }

    private function normalizeColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
            throw new RuntimeException('Color must be a six-digit hexadecimal value.');
        }

        return strtolower($color);
    }
}
