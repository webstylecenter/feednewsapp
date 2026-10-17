<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\User\OAuth\OAuthService;
use App\User\OAuth\OAuthUserService;
use App\User\Service\UserLoginTracker;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
        OAuthUserService $oauthUserService,
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

            $user = $oauthUserService->findOrCreate($profile);

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
            'google' => [self::envString('GOOGLE_CLIENT_ID'), self::envString('GOOGLE_CLIENT_SECRET')],
            'facebook' => [self::envString('FACEBOOK_CLIENT_ID'), self::envString('FACEBOOK_CLIENT_SECRET')],
            default => throw new RuntimeException('Unsupported OAuth provider.'),
        };
    }

    private static function envString(string $key): string
    {
        $value = $_ENV[$key] ?? null;

        return is_string($value) ? $value : '';
    }
}
