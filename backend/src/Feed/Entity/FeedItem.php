<?php

declare(strict_types=1);

namespace App\Feed\Entity;

use App\Feed\Repository\FeedItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: FeedItemRepository::class)]
#[ORM\Table(name: 'feed_item')]
class FeedItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: Types::STRING)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Feed::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private ?Feed $feed = null;

    #[ORM\Column(type: Types::STRING)]
    private string $guid;

    #[ORM\Column(type: Types::TEXT)]
    private string $url;

    public function __construct(string $title, ?string $description, ?Feed $feed, string $guid, string $url)
    {
        $this->title = $title;
        $this->description = $description;
        $this->feed = $feed;
        $this->guid = $guid;
        $this->url = $url;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): FeedItem
    {
        $this->description = $description;
        return $this;
    }

    public function getFeed(): ?Feed
    {
        return $this->feed;
    }

    public function setFeed(?Feed $feed): FeedItem
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): FeedItem
    {
        $this->url = $url;
        return $this;
    }
}
