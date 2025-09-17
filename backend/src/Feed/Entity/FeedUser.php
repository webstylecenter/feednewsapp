<?php

declare(strict_types=1);

// src/Entity/User.php
namespace App\Feed\Entity;

use App\Feed\Repository\UserFeedRepository;
use App\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserFeedRepository::class)]
#[ORM\Table(name: 'feed_user')]
abstract class FeedUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userFeeds')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Feed::class, cascade: ['persist'])]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private Feed $feed;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $autoPin = false;

    /**
     * @var Collection<int, FeedUserItem>
     */
    #[ORM\OneToMany(targetEntity: FeedUserItem::class, mappedBy: 'userFeed', cascade: ['persist', 'remove'])]
    private Collection $items;

    public function __construct(User $user, Feed $feed, ?string $icon, ?string $color, bool $autoPin)
    {
        $this->items = new ArrayCollection();
        $this->user = $user;
        $this->feed = $feed;
        $this->icon = $icon;
        $this->color = $color;
        $this->autoPin = $autoPin;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): FeedUser
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): FeedUser
    {
        $this->user = $user;
        return $this;
    }

    public function getFeed(): Feed
    {
        return $this->feed;
    }

    public function setFeed(Feed $feed): FeedUser
    {
        $this->feed = $feed;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): FeedUser
    {
        $this->icon = $icon;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): FeedUser
    {
        $this->color = $color;
        return $this;
    }

    public function isAutoPin(): bool
    {
        return $this->autoPin;
    }

    public function setAutoPin(bool $autoPin): FeedUser
    {
        $this->autoPin = $autoPin;
        return $this;
    }

    /**
     * @return Collection<int, FeedUserItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function setItems(FeedUserItem ...$items): FeedUser
    {
        $this->items = new ArrayCollection();

        foreach ($items as $item) {
            $this->addUserFeedItem($item);
        }

        return $this;
    }

    public function addUserFeedItem(FeedUserItem $userFeedItem): FeedUser
    {
        if (!$this->items->contains($userFeedItem)) {
            $this->items->add($userFeedItem);
            $userFeedItem->setUserFeed($this);
        }
        return $this;
    }
}
