<?php

declare(strict_types=1);

namespace App\Feed\Entity;

use App\Feed\Repository\FeedItemContentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeedItemContentRepository::class)]
#[ORM\Table(name: 'feed_item_content')]
class FeedItemContent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\OneToOne(targetEntity: FeedItem::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private FeedItem $feedItem;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    public function __construct(FeedItem $feedItem, ?string $content)
    {
        $this->feedItem = $feedItem;
        $this->content = $content;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }

    public function setFeedItem(FeedItem $feedItem): FeedItemContent
    {
        $this->feedItem = $feedItem;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): FeedItemContent
    {
        $this->content = $content;
        return $this;
    }
}
