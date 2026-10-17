<?php

declare(strict_types=1);

namespace App\User\Security;

use App\User\Entity\User;
use App\User\Service\UserLoginTracker;
use App\User\Service\UserPresenter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UserLoginTracker $loginTracker,
        private UserPresenter $presenter,
        private LoginThrottle $loginThrottle,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Authentication failed.'], Response::HTTP_UNAUTHORIZED);
        }

        $this->loginThrottle->clear($request);
        $this->loginTracker->track($user, $request);

        return new JsonResponse([
            'status' => 'success',
            'user' => $this->presenter->present($user),
        ]);
    }
}
