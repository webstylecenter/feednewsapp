<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ChecklistItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: ChecklistItemRepository::class)]
#[ORM\Table(name: 'checklist_items')]
final class ChecklistItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::STRING)]
    private string $item;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $checked;

    public function __construct(string $item, bool $checked)
    {
        $this->item = $item;
        $this->checked = $checked;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ChecklistItem
    {
        $this->id = $id;
        return $this;
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function setItem(string $item): ChecklistItem
    {
        $this->item = $item;
        return $this;
    }

    public function getChecked(): bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): ChecklistItem
    {
        $this->checked = $checked;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): ChecklistItem
    {
        $this->user = $user;
        return $this;
    }
}
