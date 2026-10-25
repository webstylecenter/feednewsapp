<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class ApiCsrfSubscriber implements EventSubscriberInterface
{
    private const string TOKEN_ID = 'feednews-api';

    public function __construct(private CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->requiresCsrfProtection($request)) {
            return;
        }

        $token = (string) $request->headers->get('X-CSRF-TOKEN');
        if ($token !== '' && $this->csrfTokenManager->isTokenValid(new CsrfToken(self::TOKEN_ID, $token))) {
            return;
        }

        $event->setResponse(new JsonResponse(
            [
                'status' => 'error',
                'code' => 'invalid_csrf_token',
                'message' => 'Invalid or missing CSRF token.',
            ],
            Response::HTTP_FORBIDDEN,
        ));
    }

    public function tokenId(): string
    {
        return self::TOKEN_ID;
    }

    private function requiresCsrfProtection(Request $request): bool
    {
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return false;
        }

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        return !in_array($request->getPathInfo(), [
            '/api/auth/login',
            '/api/auth/register',
        ], true);
    }
}
