<?php

declare(strict_types=1);

namespace App\User\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private LoginThrottle $loginThrottle)
    {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->loginThrottle->recordFailure($request);

        return new JsonResponse(
            ['status' => 'error', 'message' => 'Invalid email address or password.'],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
