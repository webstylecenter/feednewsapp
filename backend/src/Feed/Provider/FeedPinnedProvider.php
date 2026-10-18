<?php

declare(strict_types=1);

namespace App\Feed\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Dto\Response\FeedItemsResponse;
use App\Feed\Mapper\FeedItemMapper;
use App\Feed\Service\FeedService;
use App\Shared\Security\CurrentUser;
use Override;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<FeedItemsResponse> */
final readonly class FeedPinnedProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CurrentUser $currentUser,
        private FeedService $feedService,
        private FeedItemMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): FeedItemsResponse
    {
        $source = trim($this->requestStack->getCurrentRequest()?->query->getString('feed') ?? '');
        $manualOnly = $source === 'manual';
        $feedUuid = null;
        if ($source !== '' && !$manualOnly) {
            if (!Uuid::isValid($source)) {
                throw new BadRequestHttpException('Invalid feed filter.');
            }
            $feedUuid = $source;
        }

        return new FeedItemsResponse(
            $this->mapper->toResponses(
                $this->feedService->pinned(
                    $this->currentUser->get(),
                    null,
                    $feedUuid,
                    $manualOnly,
                ),
            ),
        );
    }
}
