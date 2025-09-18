<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

use DateTimeImmutable;

final class UserResponse
{
    public int $id;
    public string $name;
    public string $email;
    public bool $hideXFrameNotice;
    public bool $enabled;
    public bool $isAdmin;
    public ?string $avatar;
    /** @var array<int, string> $roles */
    public array $roles;
    public ?DateTimeImmutable $lastLogin;

    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        int $id,
        string $name,
        string $email,
        bool $hideXFrameNotice,
        bool $enabled,
        bool $isAdmin,
        ?string $avatar,
        array $roles,
        ?DateTimeImmutable $lastLogin
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->hideXFrameNotice = $hideXFrameNotice;
        $this->enabled = $enabled;
        $this->isAdmin = $isAdmin;
        $this->avatar = $avatar;
        $this->roles = $roles;
        $this->lastLogin = $lastLogin;
    }
}
