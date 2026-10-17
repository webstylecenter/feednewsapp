<?php

declare(strict_types=1);

namespace App\User\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LoginThrottleSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoginThrottle $loginThrottle)
    {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 40],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getPathInfo() !== '/api/auth/login' || $request->getMethod() !== 'POST') {
            return;
        }

        $retryAfter = $this->loginThrottle->retryAfter($request);
        if ($retryAfter === null) {
            return;
        }

        $response = new JsonResponse(
            ['status' => 'error', 'message' => 'Too many login attempts. Try again later.'],
            Response::HTTP_TOO_MANY_REQUESTS,
        );
        $response->headers->set('Retry-After', (string) $retryAfter);
        $event->setResponse($response);
    }
}
