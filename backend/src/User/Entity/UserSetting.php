<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\User\Repository\UserSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserSettingRepository::class)]
#[ORM\Table(name: 'user_setting')]
class UserSetting
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::STRING)]
    private string $setting;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $value;

    public function __construct(User $user, string $setting, ?string $value)
    {
        $this->user = $user;
        $this->setting = $setting;
        $this->value = $value;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UserSetting
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): UserSetting
    {
        $this->user = $user;
        return $this;
    }

    public function getSetting(): string
    {
        return $this->setting;
    }

    public function setSetting(string $setting): UserSetting
    {
        $this->setting = $setting;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): UserSetting
    {
        $this->value = $value;
        return $this;
    }
}
