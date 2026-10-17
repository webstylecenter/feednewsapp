<?php

declare(strict_types=1);

namespace App\User\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Feed\Repository\UserFeedRepository;
use App\Shared\EventSubscriber\ApiCsrfSubscriber;
use App\User\Dto\Response\OAuthAvailabilityResponse;
use App\User\Dto\Response\SessionResponse;
use App\User\Entity\User;
use App\User\Mapper\CurrentUserMapper;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProviderInterface<SessionResponse> */
final readonly class SessionProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private CurrentUserMapper $mapper,
        private UserFeedRepository $userFeedRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private ApiCsrfSubscriber $csrfSubscriber,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SessionResponse
    {
        $user = $this->security->getUser();
        $authenticatedUser = $user instanceof User ? $user : null;

        return new SessionResponse(
            user: $authenticatedUser !== null ? $this->mapper->toResponse($authenticatedUser) : null,
            firstVisit: $authenticatedUser !== null
                && $this->userFeedRepository->count(['user' => $authenticatedUser]) === 0,
            csrfToken: $this->csrfTokenManager->getToken($this->csrfSubscriber->tokenId())->getValue(),
            oauth: new OAuthAvailabilityResponse(
                google: (string) ($_ENV['GOOGLE_CLIENT_ID'] ?? '') !== '',
                facebook: (string) ($_ENV['FACEBOOK_CLIENT_ID'] ?? '') !== '',
            ),
        );
    }
}
