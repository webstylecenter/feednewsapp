<?php

declare(strict_types=1);

namespace App\User\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\EventSubscriber\ApiCsrfSubscriber;
use App\User\Dto\Request\RegisterRequest;
use App\User\Dto\Response\RegisterResponse;
use App\User\Entity\User;
use App\User\Mapper\CurrentUserMapper;
use App\User\Repository\UserRepository;
use App\User\Service\UserLoginTracker;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<RegisterRequest, RegisterResponse> */
final readonly class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private UserLoginTracker $loginTracker,
        private RequestStack $requestStack,
        private CurrentUserMapper $mapper,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private ApiCsrfSubscriber $csrfSubscriber,
    ) {
    }

    /**
     * @param RegisterRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RegisterResponse
    {
        $email = strtolower(trim($data->email));
        if ($this->userRepository->findOneBy(['email' => $email]) instanceof User) {
            throw new ConflictHttpException('An account with this email address already exists.');
        }

        $user = new User($email, trim($data->name), '', false, true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->security->login($user);
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $this->loginTracker->track($user, $request);
        }

        return new RegisterResponse(
            user: $this->mapper->toResponse($user),
            firstVisit: true,
            csrfToken: $this->csrfTokenManager->getToken($this->csrfSubscriber->tokenId())->getValue(),
        );
    }
}
