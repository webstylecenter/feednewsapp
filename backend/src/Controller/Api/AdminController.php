<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Feed\Entity\Feed;
use App\Feed\Entity\FeedError;
use App\Feed\Repository\FeedErrorRepository;
use App\Feed\Repository\FeedRepository;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use App\User\Service\UserRemovalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'api_admin_index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        FeedRepository $feedRepository,
        FeedErrorRepository $errorRepository,
    ): JsonResponse {
        $users = array_map(static fn (User $user): array => [
            'id' => $user->getId(),
            'uuid' => $user->getUuid()->toRfc4122(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'enabled' => $user->isEnabled(),
            'isAdmin' => $user->isAdmin(),
            'lastLogin' => $user->getLastLogin()?->format(DATE_ATOM),
            'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
        ], $userRepository->findBy([], ['createdAt' => 'DESC']));

        $feeds = array_map(static fn (Feed $feed): array => [
            'id' => $feed->getId(),
            'name' => $feed->getName(),
            'url' => $feed->getUrl(),
            'createdAt' => $feed->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $feed->getUpdatedAt()->format(DATE_ATOM),
        ], array_slice($feedRepository->findBy([], ['createdAt' => 'DESC']), 0, 20));

        $errors = array_map(static fn (FeedError $error): array => [
            'id' => $error->getId(),
            'type' => $error->getType()->value,
            'occurrences' => $error->getOccurrences(),
            'message' => $error->getException(),
            'user' => $error->getUser()?->getEmail(),
            'feed' => $error->getFeed()?->getName(),
            'updatedAt' => $error->getUpdatedAt()->format(DATE_ATOM),
        ], $errorRepository->findMostFrequent());

        return $this->json(['users' => $users, 'feeds' => $feeds, 'errors' => $errors]);
    }

    #[Route('/users/{id<\d+>}', name: 'api_admin_remove_user', methods: ['DELETE'])]
    public function removeUser(int $id, UserRepository $repository, UserRemovalService $removalService): JsonResponse
    {
        $user = $repository->find($id);
        if (!$user instanceof User) {
            throw $this->createNotFoundException('User not found.');
        }
        if ($user->isAdmin()) {
            return $this->json(
                ['status' => 'error', 'message' => 'Administrator accounts cannot be removed through this endpoint.'],
                Response::HTTP_CONFLICT,
            );
        }

        $removalService->remove($user);

        return $this->json(['status' => 'success']);
    }
}
