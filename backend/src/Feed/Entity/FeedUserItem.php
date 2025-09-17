<?php

declare(strict_types=1);

namespace App\Feed\Entity;

use App\Feed\Repository\UserFeedItemRepository;
use App\User\Entity\User;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserFeedItemRepository::class)]
#[ORM\Table(name: 'feed_user_item')]
final class FeedUserItem
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

    #[ORM\ManyToOne(targetEntity: FeedUser::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private ?FeedUser $userFeed = null;

    #[ORM\ManyToOne(targetEntity: FeedTag::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private ?FeedTag $tag;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $openedAt = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $viewed = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $pinned = false;

    public function __construct(User $user, FeedItem $feedItem, ?FeedUser $userFeed, ?FeedTag $tag)
    {
        $this->user = $user;
        $this->feedItem = $feedItem;
        $this->userFeed = $userFeed;
        $this->tag = $tag;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): FeedUserItem
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): FeedUserItem
    {
        $this->user = $user;
        return $this;
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }

    public function setFeedItem(FeedItem $feedItem): FeedUserItem
    {
        $this->feedItem = $feedItem;
        return $this;
    }

    public function getUserFeed(): ?FeedUser
    {
        return $this->userFeed;
    }

    public function setUserFeed(?FeedUser $userFeed): FeedUserItem
    {
        $this->userFeed = $userFeed;
        return $this;
    }

    public function getTag(): ?FeedTag
    {
        return $this->tag;
    }

    public function setTag(?FeedTag $tag): FeedUserItem
    {
        $this->tag = $tag;
        return $this;
    }

    public function getOpenedAt(): ?DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?DateTimeImmutable $openedAt): FeedUserItem
    {
        $this->openedAt = $openedAt;
        return $this;
    }

    public function isViewed(): bool
    {
        return $this->viewed;
    }

    public function setViewed(bool $viewed): FeedUserItem
    {
        $this->viewed = $viewed;
        return $this;
    }

    public function isPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): FeedUserItem
    {
        $this->pinned = $pinned;
        return $this;
    }
}
