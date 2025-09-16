<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserFeedItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserFeedItemRepository::class)]
#[ORM\Table(name: 'user_feed_items')]
final class UserFeedItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: FeedItem::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private FeedItem $feedItem;

    #[ORM\ManyToOne(targetEntity: UserFeed::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private UserFeed $userFeed;

    #[ORM\ManyToOne(targetEntity: Tag::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private ?Tag $tag;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private bool $openedAt = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $viewed = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $pinned = false;

    public function __construct(User $user, FeedItem $feedItem, UserFeed $userFeed, bool $pinned, ?Tag $tag)
    {
        $this->user = $user;
        $this->feedItem = $feedItem;
        $this->userFeed = $userFeed;
        $this->pinned = $pinned;
        $this->tag = $tag;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UserFeedItem
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): UserFeedItem
    {
        $this->user = $user;
        return $this;
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }

    public function setFeedItem(FeedItem $feedItem): UserFeedItem
    {
        $this->feedItem = $feedItem;
        return $this;
    }

    public function getUserFeed(): UserFeed
    {
        return $this->userFeed;
    }

    public function setUserFeed(UserFeed $userFeed): UserFeedItem
    {
        $this->userFeed = $userFeed;
        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): UserFeedItem
    {
        $this->tag = $tag;
        return $this;
    }

    public function isOpenedAt(): bool
    {
        return $this->openedAt;
    }

    public function setOpenedAt(bool $openedAt): UserFeedItem
    {
        $this->openedAt = $openedAt;
        return $this;
    }

    public function isViewed(): bool
    {
        return $this->viewed;
    }

    public function setViewed(bool $viewed): UserFeedItem
    {
        $this->viewed = $viewed;
        return $this;
    }

    public function isPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): UserFeedItem
    {
        $this->pinned = $pinned;
        return $this;
    }
}
