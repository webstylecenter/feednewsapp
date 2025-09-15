<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'notes')]
final class Note
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

    #[ORM\Column(type: Types::INTEGER, length: 6)]
    private int $position = 0;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    public function __construct(User $user, string $name, int $position, string $content)
    {
        $this->user = $user;
        $this->name = $name;
        $this->position = $position;
        $this->content = $content;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Note
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Note
    {
        $this->name = $name;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): Note
    {
        $this->position = $position;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): Note
    {
        $this->content = $content;
        return $this;
    }
}
