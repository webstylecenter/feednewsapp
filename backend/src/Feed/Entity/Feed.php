<?php

declare(strict_types=1);

namespace App\Feed\Entity;

use App\Feed\Repository\FeedRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: FeedRepository::class)]
#[ORM\Table(name: 'feed')]
class Feed
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: FeedCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?FeedCategory $category = null;
    #[ORM\Column(type: Types::STRING)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 191)]
    private string $color;

    #[ORM\Column(type: Types::TEXT)]
    private string $url;

    public function __construct(?FeedCategory $category, string $name, string $color, string $url)
    {
        $this->category = $category;
        $this->name = $name;
        $this->color = $color;
        $this->url = $url;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCategory(): ?FeedCategory
    {
        return $this->category;
    }

    public function setCategory(?FeedCategory $category): Feed
    {
        $this->category = $category;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Feed
    {
        $this->name = $name;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): Feed
    {
        $this->color = $color;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Feed
    {
        $this->url = $url;
        return $this;
    }
}
