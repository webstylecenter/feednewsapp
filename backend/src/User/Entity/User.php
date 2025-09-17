<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\Feed\Entity\FeedUser;
use App\User\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use InvalidArgumentException;
use SensitiveParameter;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::STRING, unique: false)]
    private string $name;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $hideXFrameNotice = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isAdmin = false;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $avatar = null;

    /** @var array<int, string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(type: Types::STRING)]
    private string $password;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastLogin = null;

    /** @var Collection<int, FeedUser> */
    #[ORM\OneToMany(targetEntity: FeedUser::class, mappedBy: 'user', orphanRemoval: false)]
    private Collection $userFeeds;

    public function __construct(
        string $email,
        string $name,
        #[SensitiveParameter] string $password,
        bool $hideXFrameNotice,
        bool $enabled,
        ?string $avatar = null,
    ) {
        if ($email === '') {
            throw new InvalidArgumentException('Email cannot be empty.');
        }

        $this->email = $email;
        $this->name = $name;
        $this->password = $password;
        $this->hideXFrameNotice = $hideXFrameNotice;
        $this->enabled = $enabled;
        $this->avatar = $avatar;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): User
    {
        if ($email === '') {
            throw new InvalidArgumentException('Email cannot be empty.');
        }

        $this->email = $email;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): User
    {
        $this->name = $name;
        return $this;
    }

    public function isHideXFrameNotice(): bool
    {
        return $this->hideXFrameNotice;
    }

    public function setHideXFrameNotice(bool $hideXFrameNotice): User
    {
        $this->hideXFrameNotice = $hideXFrameNotice;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): User
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): User
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): User
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(string ...$roles): User
    {
        foreach ($roles as $role) {
            $this->addRole($role);
        }
        return $this;
    }

    public function addRole(string $role): User
    {
        if (in_array($role, $this->roles, true)) {
            return $this;
        }

        $this->roles[] = $role;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(#[SensitiveParameter] string $password): User
    {
        $this->password = $password;
        return $this;
    }

    public function getLastLogin(): ?DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTimeImmutable $lastLogin): User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        if ($this->email === '') {
            throw new InvalidArgumentException('Email cannot be empty.');
        }

        return $this->email;
    }

    /**
     * @return Collection<int, FeedUser>
     */
    public function getUserFeeds(): Collection
    {
        return $this->userFeeds;
    }

    /**
     * @param Collection<int, FeedUser> $userFeeds
     */
    public function setUserFeeds(Collection $userFeeds): User
    {
        $this->userFeeds = $userFeeds;
        return $this;
    }

    public function addUserFeed(FeedUser $userFeed): User
    {
        $this->userFeeds->add($userFeed);
        return $this;
    }
}
