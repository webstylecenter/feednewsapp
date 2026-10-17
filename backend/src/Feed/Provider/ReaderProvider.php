<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\ReaderResponse;
use App\Feed\Service\ReaderService;
use InvalidArgumentException;
use Override;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<ReaderResponse> */
final readonly class ReaderProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ReaderService $readerService,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ReaderResponse
    {
        $url = $this->requestStack->getCurrentRequest()?->query->get('url');
        if (!is_string($url) || trim($url) === '') {
            throw new InvalidArgumentException('URL is required.');
        }

        return $this->readerService->fetch($url);
    }
}
