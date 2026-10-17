<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 64],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $throwable = $event->getThrowable();

        $this->logger->error(
            'Unhandled API exception.',
            [
                'exception' => $throwable,
                'path' => $request->getPathInfo(),
            ],
        );

        $statusCode = match (true) {
            $throwable instanceof HttpExceptionInterface => $throwable->getStatusCode(),
            $throwable instanceof InvalidArgumentException,
            $throwable instanceof RuntimeException => Response::HTTP_UNPROCESSABLE_ENTITY,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        $message = $statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR && $this->debug === false
            ? 'An unexpected server error occurred.'
            : $throwable->getMessage();

        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($this->debug) {
            $response['exception'] = $throwable::class;
            $response['file'] = $throwable->getFile();
            $response['line'] = $throwable->getLine();
        }

        $event->setResponse(new JsonResponse($response, $statusCode));
    }
}
