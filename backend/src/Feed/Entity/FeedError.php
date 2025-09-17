<?php

declare(strict_types=1);

namespace App\Feed\Entity;

use App\Entity\Enum\ErrorType;
use App\Feed\Repository\FeedErrorRepository;
use App\User\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: FeedErrorRepository::class)]
#[ORM\Table(name: 'errors')]
final class FeedError
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: Types::STRING, enumType: ErrorType::class)]
    private ErrorType $type;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Feed::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private ?Feed $feed = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private string $exception;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 1])]
    private int $occurrences = 1;

    public function __construct(ErrorType $type, ?User $user, ?Feed $feed, string $exception)
    {
        $this->type = $type;
        $this->user = $user;
        $this->feed = $feed;
        $this->exception = $exception;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): FeedError
    {
        $this->id = $id;
        return $this;
    }

    public function getType(): ErrorType
    {
        return $this->type;
    }

    public function setType(ErrorType $type): FeedError
    {
        $this->type = $type;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): FeedError
    {
        $this->user = $user;
        return $this;
    }

    public function getFeed(): ?Feed
    {
        return $this->feed;
    }

    public function setFeed(?Feed $feed): FeedError
    {
        $this->feed = $feed;
        return $this;
    }

    public function getException(): string
    {
        return $this->exception;
    }

    public function setException(string $exception): FeedError
    {
        $this->exception = $exception;
        return $this;
    }

    public function getOccurrences(): int
    {
        return $this->occurrences;
    }

    public function setOccurrences(int $occurrences): FeedError
    {
        $this->occurrences = $occurrences;
        return $this;
    }
}
