<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FeedItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: FeedItemRepository::class)]
#[ORM\Table(name: 'feed_items')]
final class FeedItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: Types::STRING)]
    private string $title;

    #[ORM\Column(type: Types::STRING)]
    private string $description;

    #[ORM\ManyToOne(targetEntity: Feed::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private Feed $feed;

    #[ORM\Column(type: Types::STRING)]
    private string $guid;

    #[ORM\OneToOne(targetEntity: FeedItemContent::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FeedItemContent $content;

    public function __construct(string $title, string $description, Feed $feed, string $guid, FeedItemContent $content)
    {
        $this->title = $title;
        $this->description = $description;
        $this->feed = $feed;
        $this->guid = $guid;
        $this->content = $content;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): FeedItem
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): FeedItem
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): FeedItem
    {
        $this->description = $description;
        return $this;
    }

    public function getFeed(): Feed
    {
        return $this->feed;
    }

    public function setFeed(Feed $feed): FeedItem
    {
        $this->feed = $feed;
        return $this;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function setGuid(string $guid): FeedItem
    {
        $this->guid = $guid;
        return $this;
    }

    public function getContent(): FeedItemContent
    {
        return $this->content;
    }

    public function setContent(FeedItemContent $content): FeedItem
    {
        $this->content = $content;
        return $this;
    }
}
