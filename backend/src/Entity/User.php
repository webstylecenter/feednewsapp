<?php

declare(strict_types=1);

// src/Entity/User.php
namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
abstract class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::STRING, unique: false)]
    private string $mame;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $hideXFrameNotice = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isAdmin = false;

    #[ORM\Column(type: Types::STRING)]
    private ?string $avatar = null;

    /** @var array<int, string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(type: Types::STRING)]
    private string $password;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $lastLogin;

    public function __construct(
        string $email,
        string $mame,
        bool $hideXFrameNotice,
        bool $enabled,
        ?string $avatar = null,
    ) {
        $this->email = $email;
        $this->mame = $mame;
        $this->hideXFrameNotice = $hideXFrameNotice;
        $this->enabled = $enabled;
        $this->avatar = $avatar;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): User
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    public function getMame(): string
    {
        return $this->mame;
    }

    public function setMame(string $mame): User
    {
        $this->mame = $mame;
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

    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    public function getLastLogin(): DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(DateTimeImmutable $lastLogin): User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }
}
