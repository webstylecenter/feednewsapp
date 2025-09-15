<?php

namespace App\Entity;

use App\Repository\FeedRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: FeedRepository::class)]
#[ORM\Table(name: 'feeds')]
final class Feed
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $url;

    #[ORM\OneToMany(targetEntity: FeedCategory::class, mappedBy: 'feed')]
    private FeedCategory $category;

    public function __construct(string $name, string $url, FeedCategory $category)
    {
        $this->name = $name;
        $this->url = $url;
        $this->category = $category;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Feed
    {
        $this->id = $id;
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Feed
    {
        $this->url = $url;
        return $this;
    }

    public function getCategory(): FeedCategory
    {
        return $this->category;
    }

    public function setCategory(FeedCategory $category): Feed
    {
        $this->category = $category;
        return $this;
    }
}
