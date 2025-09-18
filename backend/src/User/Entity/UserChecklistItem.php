<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\User\Repository\UserChecklistItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserChecklistItemRepository::class)]
#[ORM\Table(name: 'user_checklist_item')]
class UserChecklistItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::STRING)]
    private string $item;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $checked;

    public function __construct(User $user, string $item, bool $checked)
    {
        $this->user = $user;
        $this->item = $item;
        $this->checked = $checked;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): UserChecklistItem
    {
        $this->user = $user;
        return $this;
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function setItem(string $item): UserChecklistItem
    {
        $this->item = $item;
        return $this;
    }

    public function getChecked(): bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): UserChecklistItem
    {
        $this->checked = $checked;
        return $this;
    }
}
