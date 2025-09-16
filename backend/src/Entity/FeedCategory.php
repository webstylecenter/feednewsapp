<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FeedCategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeedCategoryRepository::class)]
#[ORM\Table(name: 'feed_categories')]
final class FeedCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, unique: true)]
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): FeedCategory
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): FeedCategory
    {
        $this->name = $name;
        return $this;
    }
}
