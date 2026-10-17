<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Feed\Repository\UserFeedRepository;
use App\Shared\EventSubscriber\ApiCsrfSubscriber;
use App\Shared\Request\RequestData;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use App\User\Service\UserLoginTracker;
use App\User\Service\UserPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly UserPresenter $userPresenter,
        private readonly UserFeedRepository $userFeedRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly ApiCsrfSubscriber $csrfSubscriber,
    ) {
    }

    #[Route('/api/auth/session', name: 'api_auth_session', methods: ['GET'])]
    public function session(): JsonResponse
    {
        $user = $this->security->getUser();

        return $this->json([
            'user' => $user instanceof User ? $this->userPresenter->present($user) : null,
            'firstVisit' => $user instanceof User && $this->userFeedRepository->count(['user' => $user]) === 0,
            'csrfToken' => $this->csrfTokenManager->getToken($this->csrfSubscriber->tokenId())->getValue(),
            'oauth' => [
                'google' => (string) ($_ENV['GOOGLE_CLIENT_ID'] ?? '') !== '',
                'facebook' => (string) ($_ENV['FACEBOOK_CLIENT_ID'] ?? '') !== '',
            ],
        ]);
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserLoginTracker $loginTracker,
    ): JsonResponse {
        $data = RequestData::all($request);
        $name = RequestData::string($data, 'name');
        $email = strtolower(RequestData::string($data, 'email'));
        $password = RequestData::string($data, 'password');
        $address = RequestData::string($data, 'address');
        $gdpr = RequestData::bool($data, 'gdpr');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($userRepository->findOneBy(['email' => $email]) instanceof User) {
            $errors['email'] = 'An account with this email address already exists.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must contain at least 8 characters.';
        }
        if ($address !== '') {
            $errors['address'] = 'Registration rejected.';
        }
        if ($gdpr === false) {
            $errors['gdpr'] = 'You must agree to the privacy policy.';
        }

        if ($errors !== []) {
            return $this->json(['status' => 'error', 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = new User($email, $name, '', false, true);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $entityManager->persist($user);
        $entityManager->flush();

        $this->security->login($user);
        $loginTracker->track($user, $request);

        return $this->json([
            'status' => 'success',
            'user' => $this->userPresenter->present($user),
            'firstVisit' => true,
            'csrfToken' => $this->csrfTokenManager->getToken($this->csrfSubscriber->tokenId())->getValue(),
        ], Response::HTTP_CREATED);
    }
}
