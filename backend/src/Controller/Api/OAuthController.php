<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\User\Entity\User;
use App\User\OAuth\OAuthService;
use App\User\Repository\UserRepository;
use App\User\Service\UserLoginTracker;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OAuthController extends AbstractController
{
    #[Route('/api/auth/oauth/{provider}', name: 'api_auth_oauth_start', requirements: ['provider' => 'google|facebook'], methods: ['GET'])]
    public function start(string $provider, Request $request, OAuthService $oauthService): RedirectResponse
    {
        [$clientId] = $this->credentials($provider);
        if ($clientId === '') {
            throw $this->createNotFoundException('This OAuth provider is not configured.');
        }

        $state = bin2hex(random_bytes(24));
        $request->getSession()->set('oauth_state_' . $provider, $state);
        $redirectUri = $this->generateUrl(
            'api_auth_oauth_callback',
            ['provider' => $provider],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return $this->redirect($oauthService->authorizationUrl($provider, $clientId, $redirectUri, $state));
    }

    #[Route('/api/auth/oauth/{provider}/callback', name: 'api_auth_oauth_callback', requirements: ['provider' => 'google|facebook'], methods: ['GET'])]
    public function callback(
        string $provider,
        Request $request,
        OAuthService $oauthService,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
        UserLoginTracker $loginTracker,
    ): RedirectResponse {
        $expectedState = $request->getSession()->remove('oauth_state_' . $provider);
        $state = $request->query->getString('state');
        $code = $request->query->getString('code');
        if (!is_string($expectedState) || $expectedState === '' || !hash_equals($expectedState, $state) || $code === '') {
            return $this->redirect('/login?oauth=failed');
        }

        try {
            [$clientId, $clientSecret] = $this->credentials($provider);
            $redirectUri = $this->generateUrl(
                'api_auth_oauth_callback',
                ['provider' => $provider],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
            $profile = $oauthService->fetchProfile($provider, $code, $clientId, $clientSecret, $redirectUri);

            $user = $userRepository->findOneBy(['email' => strtolower($profile->email)]);
            if (!$user instanceof User) {
                $user = new User(strtolower($profile->email), $profile->name, '', false, true, $profile->avatar);
                $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
                $entityManager->persist($user);
            } else {
                $user->setName($profile->name)->setAvatar($profile->avatar);
            }
            $entityManager->flush();

            $security->login($user);
            $loginTracker->track($user, $request);
        } catch (RuntimeException) {
            return $this->redirect('/login?oauth=failed');
        }

        return $this->redirect('/');
    }

    /** @return array{0: string, 1: string} */
    private function credentials(string $provider): array
    {
        return match ($provider) {
            'google' => [(string) ($_ENV['GOOGLE_CLIENT_ID'] ?? ''), (string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? '')],
            'facebook' => [(string) ($_ENV['FACEBOOK_CLIENT_ID'] ?? ''), (string) ($_ENV['FACEBOOK_CLIENT_SECRET'] ?? '')],
            default => throw new RuntimeException('Unsupported OAuth provider.'),
        };
    }
}
