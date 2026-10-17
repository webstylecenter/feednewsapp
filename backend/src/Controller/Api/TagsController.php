<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Feed\Entity\FeedTag;
use App\Feed\Entity\FeedUserItem;
use App\Feed\Repository\FeedTagRepository;
use App\Feed\Repository\UserFeedItemRepository;
use App\Shared\Request\RequestData;
use App\Shared\Security\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tags')]
final class TagsController extends AbstractController
{
    #[Route('', name: 'api_tags_index', methods: ['GET'])]
    public function index(
        CurrentUser $currentUser,
        FeedTagRepository $tagRepository,
        UserFeedItemRepository $itemRepository,
    ): JsonResponse {
        $user = $currentUser->get();
        $items = [];
        foreach ($tagRepository->findForUser($user) as $tag) {
            $id = $tag->getId();
            if ($id === null) {
                continue;
            }
            $items[] = [
                'id' => $id,
                'uuid' => $tag->getUuid()->toRfc4122(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
                'count' => $itemRepository->countForTag($user, $tag),
            ];
        }

        return $this->json(['items' => $items]);
    }

    #[Route('', name: 'api_tags_add', methods: ['POST'])]
    public function add(
        Request $request,
        CurrentUser $currentUser,
        FeedTagRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = RequestData::all($request);
        $name = RequestData::string($data, 'name');
        $color = RequestData::string($data, 'color');
        if (mb_strlen($name) < 3 || preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
            return $this->json(['status' => 'error', 'message' => 'Enter a tag name and a valid hex color.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $currentUser->get();
        if ($repository->findOneBy(['user' => $user, 'name' => $name]) instanceof FeedTag) {
            return $this->json(['status' => 'error', 'message' => 'A tag with that name already exists.'], Response::HTTP_CONFLICT);
        }

        $tag = new FeedTag($user, mb_substr($name, 0, 255), strtolower($color));
        $entityManager->persist($tag);
        $entityManager->flush();

        return $this->json(['status' => 'success', 'id' => $tag->getId()], Response::HTTP_CREATED);
    }

    #[Route('/assign', name: 'api_tags_assign', methods: ['POST'])]
    public function assign(
        Request $request,
        CurrentUser $currentUser,
        FeedTagRepository $tagRepository,
        UserFeedItemRepository $itemRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = RequestData::all($request);
        $itemId = RequestData::int($data, 'itemId') ?? RequestData::int($data, 'user-feed-item-id');
        $tagId = RequestData::int($data, 'tagId') ?? RequestData::int($data, 'tag');
        if ($itemId === null) {
            return $this->json(['status' => 'error', 'message' => 'Feed item is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $currentUser->get();
        $item = $itemRepository->findOwnedById($user, $itemId);
        if (!$item instanceof FeedUserItem) {
            throw $this->createNotFoundException('Feed item not found.');
        }

        $tag = $tagId !== null ? $tagRepository->findOwnedById($user, $tagId) : null;
        if ($tagId !== null && !$tag instanceof FeedTag) {
            throw $this->createNotFoundException('Tag not found.');
        }

        $item->setTag($tag);
        $entityManager->flush();
        return $this->json(['status' => 'success']);
    }

    #[Route('/{id<\d+>}', name: 'api_tags_remove', methods: ['DELETE'])]
    public function remove(
        int $id,
        CurrentUser $currentUser,
        FeedTagRepository $tagRepository,
        UserFeedItemRepository $itemRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $currentUser->get();
        $tag = $tagRepository->findOwnedById($user, $id);
        if (!$tag instanceof FeedTag) {
            throw $this->createNotFoundException('Tag not found.');
        }

        foreach ($itemRepository->findByTagForUser($user, $tag) as $item) {
            $item->setTag(null);
        }
        $entityManager->remove($tag);
        $entityManager->flush();
        return $this->json(['status' => 'success']);
    }
}
