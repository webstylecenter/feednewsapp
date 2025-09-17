<?php

declare(strict_types=1);

namespace App\Feed\Entity;

use App\Entity\User;
use App\Feed\Repository\FeedTagRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: FeedTagRepository::class)]
#[ORM\Table(name: 'tags')]
final class FeedTag
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private User $user;

    #[ORM\Column(type: Types::STRING)]
    private string $name;

    #[ORM\Column(type: Types::STRING)]
    private string $color;

    public function __construct(User $user, string $name, string $color)
    {
        $this->user = $user;
        $this->name = $name;
        $this->color = $color;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): FeedTag
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): FeedTag
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): FeedTag
    {
        $this->name = $name;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): FeedTag
    {
        $this->color = $color;
        return $this;
    }
}
