<?php

declare(strict_types=1);

namespace App\Feed\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Feed\Dto\Request\CreateTagRequest;
use App\Feed\Dto\Response\CreateTagResponse;
use App\Feed\Entity\FeedTag;
use App\Feed\Repository\FeedTagRepository;
use App\Shared\Security\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Override;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** @implements ProcessorInterface<CreateTagRequest, CreateTagResponse> */
final readonly class CreateTagProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FeedTagRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param CreateTagRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CreateTagResponse
    {
        $user = $this->currentUser->get();
        $name = trim($data->name);
        if ($this->repository->findOneBy(['user' => $user, 'name' => $name]) instanceof FeedTag) {
            throw new ConflictHttpException('A tag with that name already exists.');
        }

        $tag = new FeedTag($user, mb_substr($name, 0, 255), strtolower($data->color));
        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $id = $tag->getId();
        if ($id === null) {
            throw new LogicException('Persisted tag has no identifier.');
        }

        return new CreateTagResponse($id);
    }
}
