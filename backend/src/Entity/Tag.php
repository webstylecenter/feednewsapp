<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NoteRepository;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tags')]
final class Tag
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private User $user;

    /**
     * @var null|Collection<int, UserFeedItem>
     */
    #[ORM\OneToMany(targetEntity: UserFeedItem::class, mappedBy: 'tag')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private ?Collection $userFeedItems;

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

    public function setId(int $id): Tag
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Tag
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, UserFeedItem>
     */
    public function getUserFeedItems(): Collection
    {
        return $this->userFeedItems;
    }

    public function setUserFeedItems(UserFeedItem ...$userFeedItems): Tag
    {
        $this->userFeedItems = [];
        foreach ($userFeedItems as $userFeedItem) {
            $this->addUserFeedItem($userFeedItem);
        }
    }

    public function addUserFeedItem(UserFeedItem $userFeedItem): Tag
    {
        $this->userFeedItems[] = $userFeedItem;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Tag
    {
        $this->name = $name;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): Tag
    {
        $this->color = $color;
        return $this;
    }
}
