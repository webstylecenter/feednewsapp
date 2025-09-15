<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\ErrorType;
use App\Repository\ErrorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: ErrorRepository::class)]
#[ORM\Table(name: 'errors')]
final class Error
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: Types::STRING, enumType: ErrorType::class)]
    private ErrorType $type;

    private User $user;

    private Feed $feed;

    private string $exception;

    private int $occurrences = 0;

    public function __construct(ErrorType $type, User $user, Feed $feed, string $exception, int $occurrences)
    {
        $this->type = $type;
        $this->user = $user;
        $this->feed = $feed;
        $this->exception = $exception;
        $this->occurrences = $occurrences;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Error
    {
        $this->id = $id;
        return $this;
    }

    public function getType(): ErrorType
    {
        return $this->type;
    }

    public function setType(ErrorType $type): Error
    {
        $this->type = $type;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Error
    {
        $this->user = $user;
        return $this;
    }

    public function getFeed(): Feed
    {
        return $this->feed;
    }

    public function setFeed(Feed $feed): Error
    {
        $this->feed = $feed;
        return $this;
    }

    public function getException(): string
    {
        return $this->exception;
    }

    public function setException(string $exception): Error
    {
        $this->exception = $exception;
        return $this;
    }

    public function getOccurrences(): int
    {
        return $this->occurrences;
    }

    public function setOccurrences(int $occurrences): Error
    {
        $this->occurrences = $occurrences;
        return $this;
    }
}
